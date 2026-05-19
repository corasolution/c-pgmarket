<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config([
        'services.aba.merchant_id' => 'test_merchant',
        'services.aba.api_key' => 'test_secret_key',
    ]);

    $buyer = User::factory()->create(['role' => 'buyer']);

    $this->order = Order::factory()->create([
        'buyer_id' => $buyer->id,
        'status' => 'pending',
        'total_cents' => 10000,
    ]);

    $this->payment = Payment::factory()->create([
        'order_id' => $this->order->id,
        'transaction_id' => 'TXN-001',
        'status' => 'pending',
        'amount_cents' => 10000,
        'amount_currency' => 'USD',
    ]);
});

/**
 * ABA webhook signature: sort payload keys alphabetically (excluding hash),
 * concatenate values, then HMAC-SHA512 Base64.
 */
function makeWebhookSignature(array $fields): string
{
    $key = 'test_secret_key';
    ksort($fields);
    $raw = implode('', array_values(array_map('strval', $fields)));

    return base64_encode(hash_hmac('sha512', $raw, $key, true));
}

test('valid webhook updates payment to paid', function (): void {
    // Mock the check-transaction-2 call to avoid real HTTP
    Http::fake([
        '*/check-transaction-2' => Http::response([
            'status' => ['code' => '00'],
            'data' => ['tran_status' => 'SUCCESS'],
        ]),
    ]);

    $fields = [
        'tran_id' => 'TXN-001',
        'status' => 'SUCCESS',
        'amount' => '100.00',
        'aba_tran' => 'ABA-REF-001',
    ];
    $fields['hash'] = makeWebhookSignature($fields);

    $response = $this->post(route('webhooks.aba-payway'), $fields);

    $response->assertStatus(200);
    $response->assertSee('Completed');
    expect($this->payment->fresh()->status)->toBe('paid');
});

test('invalid signature still returns 200 Completed but does not update payment', function (): void {
    $response = $this->post(route('webhooks.aba-payway'), [
        'tran_id' => 'TXN-001',
        'status' => 'SUCCESS',
        'amount' => '100.00',
        'hash' => 'invalid_signature',
    ]);

    // Webhook always returns 200 — ABA rejects HTML error pages
    $response->assertStatus(200);
    $response->assertSee('Completed');
    expect($this->payment->fresh()->status)->toBe('pending');
});

test('duplicate callback is idempotent', function (): void {
    $this->payment->update(['status' => 'paid']);

    $fields = [
        'tran_id' => 'TXN-001',
        'status' => 'SUCCESS',
        'amount' => '100.00',
    ];
    $fields['hash'] = makeWebhookSignature($fields);

    $response = $this->post(route('webhooks.aba-payway'), $fields);

    $response->assertStatus(200);
    expect($this->payment->fresh()->status)->toBe('paid');
});

test('malformed payload missing tran_id returns 200 Completed', function (): void {
    $fields = ['status' => 'SUCCESS'];
    $fields['hash'] = makeWebhookSignature($fields);

    $response = $this->post(route('webhooks.aba-payway'), $fields);

    // Always plain-text 200 — never HTML error pages
    $response->assertStatus(200);
    $response->assertSee('Completed');
});

test('webhook records PaymentEvent for audit trail', function (): void {
    Http::fake([
        '*/check-transaction-2' => Http::response([
            'status' => ['code' => '00'],
            'data' => ['tran_status' => 'SUCCESS'],
        ]),
    ]);

    $fields = [
        'tran_id' => 'TXN-001',
        'status' => 'SUCCESS',
        'amount' => '100.00',
        'aba_tran' => 'ABA-REF-002',
    ];
    $fields['hash'] = makeWebhookSignature($fields);

    $this->post(route('webhooks.aba-payway'), $fields);

    expect(PaymentEvent::where('payment_id', $this->payment->id)->exists())->toBeTrue();
});
