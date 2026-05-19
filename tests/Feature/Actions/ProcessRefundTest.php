<?php

declare(strict_types=1);

use App\Actions\Payment\ProcessRefund;
use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Shop;
use App\Models\SubOrder;
use App\Models\User;
use App\Models\VendorWallet;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->buyer = User::factory()->create(['role' => 'buyer']);

    $vendor = User::factory()->create(['role' => 'vendor_owner']);
    $this->shop = Shop::factory()->create(['owner_id' => $vendor->id]);

    $this->wallet = VendorWallet::factory()->create([
        'shop_id'                    => $this->shop->id,
        'available_balance_cents'    => 10000,
        'available_balance_currency' => 'USD',
        'pending_balance_cents'      => 0,
        'pending_balance_currency'   => 'USD',
        'lifetime_earned_cents'      => 10000,
    ]);

    $this->order = Order::factory()->create([
        'buyer_id' => $this->buyer->id,
        'status'   => 'delivered',
        'total_cents' => 10000,
    ]);

    SubOrder::factory()->create([
        'order_id'          => $this->order->id,
        'shop_id'           => $this->shop->id,
        'status'            => 'delivered',
        'subtotal_cents'    => 10000,
        'subtotal_currency' => 'USD',
    ]);

    $this->payment = Payment::factory()->create([
        'order_id'        => $this->order->id,
        'status'          => 'paid',
        'amount_cents'    => 10000,
        'amount_currency' => 'USD',
    ]);
});

test('admin can process a full refund', function (): void {
    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->shouldReceive('refund')->once()->andReturn(true);
    app()->instance(PaymentGateway::class, $gateway);

    app(ProcessRefund::class)($this->admin, $this->payment, 10000);

    expect($this->payment->fresh()->status)->toBe('refunded');
    expect($this->order->fresh()->status)->toBe('refunded');
});

test('non-admin cannot process a refund', function (): void {
    expect(fn () => app(ProcessRefund::class)($this->buyer, $this->payment, 5000))
        ->toThrow(AuthorizationException::class);
});

test('refund fails when gateway rejects it', function (): void {
    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->shouldReceive('refund')->once()->andReturn(false);
    app()->instance(PaymentGateway::class, $gateway);

    expect(fn () => app(ProcessRefund::class)($this->admin, $this->payment, 10000))
        ->toThrow(\RuntimeException::class);
});

test('refund amount cannot exceed original payment', function (): void {
    expect(fn () => app(ProcessRefund::class)($this->admin, $this->payment, 99999))
        ->toThrow(\DomainException::class);
});

test('cannot refund a payment that is not in paid status', function (): void {
    $this->payment->update(['status' => 'pending']);

    expect(fn () => app(ProcessRefund::class)($this->admin, $this->payment, 1000))
        ->toThrow(\DomainException::class);
});
