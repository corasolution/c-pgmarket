<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Order;
use App\Models\Payment;

interface PaymentGateway
{
    /**
     * Build signed form data for ABA PayWay popup checkout.
     * No HTTP call is made — the browser submits directly to PayWay via the JS popup.
     *
     * @return array{transaction_id: string, form_data: array<string, string>}
     */
    public function createCheckout(Order $order): array;

    /**
     * Call PayWay check-transaction-2 API to verify payment status.
     *
     * @return array{paid: bool, status: string}
     */
    public function checkPaymentStatus(string $transactionId): array;

    /**
     * Verify the HMAC-SHA512 signature on an ABA PayWay webhook POST.
     *
     * @param  array<string, mixed>  $payload  Raw POST parameters from ABA
     */
    public function verifyWebhook(array $payload): bool;

    /**
     * Process a verified callback payload: update the Payment record and return it.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleCallback(array $payload): Payment;

    public function refund(Payment $payment, int $amountCents): bool;
}
