<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Events\Payment\PaymentReceived;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvent;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ABA PayWay popup checkout gateway.
 *
 * Flow:
 *  1. createCheckout() → builds signed form fields (no HTTP call)
 *  2. Browser renders hidden form + PayWay JS popup opens via checkout2-0.js
 *  3. Customer scans KHQR with banking app
 *  4. ABA POSTs webhook to /webhooks/aba-payway
 *  5. verifyWebhook() validates HMAC-SHA512
 *  6. handleCallback() re-confirms via check-transaction-2, fires PaymentReceived
 */
final class AbaPayWayGateway implements PaymentGateway
{
    private string $merchantId;

    private string $apiKey;

    private string $paywayUrl;

    public function __construct()
    {
        $this->merchantId = (string) config('services.aba.merchant_id');
        $this->apiKey     = (string) config('services.aba.api_key');
        $this->paywayUrl  = rtrim((string) config('services.aba.payway_url', 'https://checkout-sandbox.payway.com.kh'), '/');
    }

    /**
     * Build all signed form fields for the PayWay popup checkout.
     * No HTTP call is made — the browser submits directly to PayWay via the JS popup.
     *
     * @return array{transaction_id: string, form_data: array<string, string>}
     */
    public function createCheckout(Order $order): array
    {
        $order->loadMissing('subOrders.items', 'buyer');

        $reqTime  = now()->utc()->format('YmdHis');
        $tranId   = $order->reference;
        $amount   = $this->formatAmount($order->total_cents);
        $shipping = '0.00';

        // Build items array from all sub-order items
        $items = $order->subOrders
            ->flatMap(fn ($subOrder) => $subOrder->items)
            ->map(fn ($item) => [
                'name'     => $item->product_name_snapshot,
                'quantity' => $item->quantity,
                'price'    => $this->formatAmount($item->unit_price_cents),
            ])
            ->values()
            ->toJson();

        $buyer     = $order->buyer;
        $address   = $order->shipping_address ?? [];
        $firstname = $address['name'] ?? ($buyer?->name ?? 'Customer');
        $lastname  = '';
        $email     = $buyer?->email ?? '';
        $phone     = $this->normalizePhone($address['phone'] ?? '');

        // return_url must be Base64-encoded per ABA spec
        $returnUrl          = base64_encode(route('orders.show', $order));
        $cancelUrl          = route('cart.index');
        $continueSuccessUrl = route('orders.index');
        $returnDeeplink     = '';
        $currency           = 'USD';
        $customFields       = '';
        $returnParams       = '';
        $lifetime           = '15';
        $additionalParams   = '';
        $googlePayToken     = '';
        $skipSuccessPage    = '0';
        $type               = 'purchase';
        $paymentOption      = 'abapay_khqr';

        // Hash fields must be concatenated in this exact order (empty strings included)
        $hashData = implode('', [
            $reqTime, $this->merchantId, $tranId, $amount, $items, $shipping,
            $firstname, $lastname, $email, $phone,
            $type, $paymentOption,
            $returnUrl, $cancelUrl, $continueSuccessUrl, $returnDeeplink,
            $currency, $customFields, $returnParams,
            $lifetime, $additionalParams, $googlePayToken, $skipSuccessPage,
        ]);

        // Build form data, filter out empty/null values — ABA rejects them
        $formData = array_filter([
            'action'               => "{$this->paywayUrl}/api/payment-gateway/v1/payments/purchase",
            'req_time'             => $reqTime,
            'merchant_id'          => $this->merchantId,
            'tran_id'              => $tranId,
            'amount'               => $amount,
            'items'                => $items,
            'shipping'             => $shipping,
            'firstname'            => $firstname,
            'lastname'             => $lastname,
            'email'                => $email,
            'phone'                => $phone,
            'type'                 => $type,
            'payment_option'       => $paymentOption,
            'return_url'           => $returnUrl,
            'cancel_url'           => $cancelUrl,
            'continue_success_url' => $continueSuccessUrl,
            'return_deeplink'      => $returnDeeplink,
            'currency'             => $currency,
            'custom_fields'        => $customFields,
            'return_params'        => $returnParams,
            'lifetime'             => $lifetime,
            'additional_params'    => $additionalParams,
            'google_pay_token'     => $googlePayToken,
            'skip_success_page'    => $skipSuccessPage,
            'hash'                 => $this->sign($hashData),
        ], fn ($v) => $v !== '' && $v !== null);

        // Create pending Payment record
        $order->payment()->create([
            'provider' => 'aba_payway',
            'transaction_id' => $tranId,
            'amount_cents' => $order->total_cents,
            'amount_currency' => 'USD',
            'status' => 'pending',
            'hash' => $formData['hash'],
        ]);

        return [
            'transaction_id' => $tranId,
            'form_data' => $formData,
        ];
    }

    /**
     * Call PayWay check-transaction-2 to verify payment status.
     *
     * @return array{paid: bool, status: string}
     */
    public function checkPaymentStatus(string $transactionId): array
    {
        $reqTime  = now()->utc()->format('YmdHis');
        $hashData = $reqTime.$this->merchantId.$transactionId;
        $hash     = $this->sign($hashData);

        try {
            $response = Http::timeout(15)
                ->asForm()
                ->post("{$this->paywayUrl}/api/payment-gateway/v1/payments/check-transaction-2", [
                    'req_time'    => $reqTime,
                    'merchant_id' => $this->merchantId,
                    'tran_id'     => $transactionId,
                    'hash'        => $hash,
                ]);

            $data = $response->json();

            if (isset($data['status']['code']) && $data['status']['code'] === '00') {
                $tranStatus = $data['data']['tran_status'] ?? '';

                return [
                    'paid'   => $tranStatus === 'SUCCESS',
                    'status' => strtolower($tranStatus),
                ];
            }
        } catch (RequestException $e) {
            Log::error('ABA PayWay check-transaction error', [
                'message' => $e->getMessage(),
                'tran_id' => $transactionId,
            ]);
        }

        return ['paid' => false, 'status' => 'unknown'];
    }

    /**
     * Verify the webhook signature from a PayWay pushback callback.
     * Fields are sorted alphabetically by key, values concatenated, then HMAC-SHA512.
     *
     * @param  array<string, mixed>  $payload
     */
    public function verifyWebhook(array $payload): bool
    {
        $signature = $payload['hash'] ?? '';

        if ($signature === '' || $signature === null) {
            return false;
        }

        $postData = $payload;
        unset($postData['hash']);
        ksort($postData);
        $hashData = implode('', array_values(array_map('strval', $postData)));
        $expected = $this->sign($hashData);

        return hash_equals($expected, (string) $signature);
    }

    /**
     * Process a verified webhook payload.
     * Re-confirms via check-transaction-2, records PaymentEvent for audit/idempotency.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleCallback(array $payload): Payment
    {
        $transactionId = (string) ($payload['tran_id'] ?? '');
        $payment = Payment::where('transaction_id', $transactionId)->firstOrFail();

        // Idempotent: ignore duplicate callbacks for already-processed payments
        if (in_array($payment->status, ['paid', 'refunded', 'partially_refunded'], strict: true)) {
            return $payment;
        }

        // Record the webhook event for audit trail
        $externalEventId = (string) ($payload['aba_tran'] ?? $transactionId.'-'.now()->timestamp);
        PaymentEvent::firstOrCreate(
            ['external_event_id' => $externalEventId],
            [
                'payment_id' => $payment->id,
                'provider' => 'aba_payway',
                'event_type' => 'pushback',
                'raw_payload' => $payload,
                'ip_address' => request()->ip() ?? '',
            ],
        );

        // Re-confirm via check-transaction-2 API
        $pushedStatus = (string) ($payload['status'] ?? '');
        $reconfirmed = false;

        if ($transactionId !== '') {
            $check = $this->checkPaymentStatus($transactionId);
            $reconfirmed = $check['paid'];
        }

        if ($pushedStatus === 'SUCCESS' || $reconfirmed) {
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'raw_response' => array_merge((array) ($payment->raw_response ?? []), ['callback' => $payload]),
            ]);

            event(new PaymentReceived($payment));
        } else {
            $payment->update([
                'status' => 'failed',
                'raw_response' => array_merge((array) ($payment->raw_response ?? []), ['callback' => $payload]),
            ]);

            Log::warning('ABA PayWay payment failed or not confirmed', [
                'tran_id' => $transactionId,
                'pushed_status' => $pushedStatus,
            ]);
        }

        return $payment;
    }

    public function refund(Payment $payment, int $amountCents): bool
    {
        $tranId = (string) $payment->transaction_id;
        $amount = $this->formatAmount($amountCents);
        $reqTime = now()->utc()->format('YmdHis');
        $hash = $this->sign($reqTime.$this->merchantId.$tranId.$amount);

        try {
            $response = Http::timeout(15)
                ->asForm()
                ->post("{$this->paywayUrl}/api/payment-gateway/v1/payments/refund", [
                    'req_time'    => $reqTime,
                    'merchant_id' => $this->merchantId,
                    'tran_id'     => $tranId,
                    'amount'      => $amount,
                    'hash'        => $hash,
                ]);

            return $response->successful() && ($response->json('status.code') === '00');
        } catch (RequestException $e) {
            Log::error('ABA PayWay refund error', [
                'message' => $e->getMessage(),
                'tran_id' => $tranId,
            ]);

            return false;
        }
    }

    /**
     * Normalize a Cambodian phone number to PayWay's required format: 855XXXXXXXXX
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '855')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '855'.substr($digits, 1);
        }

        return '855'.$digits;
    }

    /**
     * HMAC-SHA512 signature, Base64-encoded.
     */
    private function sign(string $data): string
    {
        return base64_encode(hash_hmac('sha512', $data, $this->apiKey, true));
    }

    private function formatAmount(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
