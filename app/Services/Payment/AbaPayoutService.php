<?php

declare(strict_types=1);

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ABA PayWay Multi-Party Payout service.
 *
 * Handles:
 * - Beneficiary whitelisting (add/update)
 * - Direct payout to vendors
 * - Pre-auth completion with payout split
 * - Transaction lookup for reconciliation
 *
 * All amounts are accepted in cents and converted to dollars at the API boundary.
 */
final class AbaPayoutService
{
    private string $merchantId;

    private string $apiKey;

    private string $paywayUrl;

    private string $rsaPublicKeyPath;

    public function __construct()
    {
        $this->merchantId       = (string) config('services.aba.merchant_id');
        $this->apiKey           = (string) config('services.aba.api_key');
        $this->paywayUrl        = rtrim((string) config('services.aba.payway_url', 'https://checkout-sandbox.payway.com.kh'), '/');
        $this->rsaPublicKeyPath = base_path((string) config('services.aba.rsa_public_key_path'));
    }

    // ──────────────────────────────────────────────
    // Beneficiary Management
    // ──────────────────────────────────────────────

    /**
     * Whitelist a beneficiary (vendor) for payout.
     * Must be called before any payout to this account.
     *
     * @param  string  $payee  ABA account number or Merchant ID
     * @return array<string, mixed>
     */
    public function addBeneficiary(string $payee): array
    {
        $reqTime = now()->utc()->format('YmdHis');

        $merchantAuth = $this->rsaEncrypt(json_encode([
            'mc_id' => $this->merchantId,
            'payee' => $payee,
        ], JSON_THROW_ON_ERROR));

        $hash = $this->sign($reqTime.$merchantAuth);

        return $this->post(
            '/api/merchant-portal/merchant-access/whitelist-account/add-whitelist-payout',
            [
                'request_time'  => $reqTime,
                'merchant_id'   => $this->merchantId,
                'merchant_auth' => $merchantAuth,
                'hash'          => $hash,
            ],
        );
    }

    /**
     * Activate or deactivate a whitelisted beneficiary.
     *
     * @param  string  $payee   ABA account number or Merchant ID
     * @param  int     $status  1 = Active, 0 = Inactive
     * @return array<string, mixed>
     */
    public function updateBeneficiaryStatus(string $payee, int $status): array
    {
        $reqTime = now()->utc()->format('YmdHis');

        $merchantAuth = $this->rsaEncrypt(json_encode([
            'mc_id'  => $this->merchantId,
            'payee'  => $payee,
            'status' => $status,
        ], JSON_THROW_ON_ERROR));

        $hash = $this->sign($reqTime.$merchantAuth);

        return $this->post(
            '/api/merchant-portal/merchant-access/whitelist-account/update-whitelist-status',
            [
                'request_time'  => $reqTime,
                'merchant_id'   => $this->merchantId,
                'merchant_auth' => $merchantAuth,
                'hash'          => $hash,
            ],
        );
    }

    // ──────────────────────────────────────────────
    // Payout
    // ──────────────────────────────────────────────

    /**
     * Distribute funds to whitelisted beneficiaries.
     *
     * @param  string                                          $tranId         Unique transaction ID (max 20 chars)
     * @param  array<int, array{account: string, amount: float}>  $beneficiaries  Max 10
     * @param  int                                             $amountCents    Total payout amount in cents
     * @param  string                                          $currency       "USD" or "KHR"
     * @return array<string, mixed>
     */
    public function payout(string $tranId, array $beneficiaries, int $amountCents, string $currency = 'USD'): array
    {
        $amount = $this->formatAmount($amountCents);
        $encryptedBeneficiaries = $this->rsaEncrypt(json_encode($beneficiaries, JSON_THROW_ON_ERROR));

        $hashData = implode('', [
            $this->merchantId,
            $tranId,
            $encryptedBeneficiaries,
            $amount,
            '', // custom_fields
            $currency,
        ]);

        $payload = [
            'merchant_id'   => $this->merchantId,
            'tran_id'       => $tranId,
            'beneficiaries' => $encryptedBeneficiaries,
            'amount'        => (float) $amount,
            'currency'      => $currency,
            'hash'          => $this->sign($hashData),
        ];

        return $this->post('/api/payment-gateway/v2/direct-payment/merchant/payout', $payload);
    }

    // ──────────────────────────────────────────────
    // Pre-Auth + Payout (Hold & Split)
    // ──────────────────────────────────────────────

    /**
     * Complete a pre-authorized transaction and distribute to vendors.
     *
     * @param  string                                      $originalTranId      Original pre-auth transaction ID
     * @param  int                                         $amountCents         Amount to capture in cents
     * @param  array<int, array{acc: string, amt: float}>  $payoutDistribution  Vendor split
     * @return array<string, mixed>
     */
    public function completePreAuthWithPayout(string $originalTranId, int $amountCents, array $payoutDistribution): array
    {
        $reqTime = now()->utc()->format('YmdHis');

        $merchantAuth = $this->rsaEncrypt(json_encode([
            'mc_id'           => $this->merchantId,
            'tran_id'         => $originalTranId,
            'complete_amount' => (float) $this->formatAmount($amountCents),
            'payout'          => $payoutDistribution,
        ], JSON_THROW_ON_ERROR));

        // Hash order is different: merchant_auth + request_time + merchant_id
        $hash = $this->sign($merchantAuth.$reqTime.$this->merchantId);

        return $this->post(
            '/api/merchant-portal/merchant-access/online-transaction/pre-auth-completion',
            [
                'request_time'  => $reqTime,
                'merchant_id'   => $this->merchantId,
                'merchant_auth' => $merchantAuth,
                'hash'          => $hash,
            ],
        );
    }

    // ──────────────────────────────────────────────
    // Transaction Lookup
    // ──────────────────────────────────────────────

    /**
     * Look up transactions by merchant reference (max 50 results).
     * Rate limited: 10 requests/minute.
     *
     * @return array<string, mixed>
     */
    public function getTransactionsByRef(string $merchantRef): array
    {
        $reqTime = now()->utc()->format('YmdHis');
        $hash = $this->sign($reqTime.$this->merchantId.$merchantRef);

        return $this->post(
            '/api/payment-gateway/v1/payments/get-transactions-by-mc-ref',
            [
                'req_time'     => $reqTime,
                'merchant_id'  => $this->merchantId,
                'merchant_ref' => $merchantRef,
                'hash'         => $hash,
            ],
        );
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    /**
     * RSA-encrypt data in 117-byte chunks using ABA's public key.
     */
    private function rsaEncrypt(string $data): string
    {
        $publicKey = openssl_pkey_get_public(file_get_contents($this->rsaPublicKeyPath));

        if ($publicKey === false) {
            throw new \RuntimeException('Failed to load ABA RSA public key from: '.$this->rsaPublicKeyPath);
        }

        $encrypted = '';
        $chunks = str_split($data, 117);

        foreach ($chunks as $chunk) {
            $encryptedChunk = '';
            openssl_public_encrypt($chunk, $encryptedChunk, $publicKey);
            $encrypted .= $encryptedChunk;
        }

        return base64_encode($encrypted);
    }

    private function sign(string $data): string
    {
        return base64_encode(hash_hmac('sha512', $data, $this->apiKey, true));
    }

    private function formatAmount(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function post(string $path, array $payload): array
    {
        try {
            $response = Http::timeout(15)
                ->asJson()
                ->post("{$this->paywayUrl}{$path}", $payload);

            return $response->json() ?? ['status' => ['code' => 'UNKNOWN', 'message' => 'Empty response']];
        } catch (\Throwable $e) {
            Log::error('ABA PayWay payout API error', [
                'path'    => $path,
                'message' => $e->getMessage(),
            ]);

            return ['status' => ['code' => 'ERROR', 'message' => $e->getMessage()]];
        }
    }
}
