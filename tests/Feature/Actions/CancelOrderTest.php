<?php

declare(strict_types=1);

use App\Actions\Order\CancelOrder;
use App\Models\Order;
use App\Models\Shop;
use App\Models\SubOrder;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function (): void {
    $this->buyer = User::factory()->create(['role' => 'buyer']);
    $this->admin = User::factory()->create(['role' => 'admin']);

    $vendor = User::factory()->create(['role' => 'vendor_owner']);
    $shop = Shop::factory()->create(['owner_id' => $vendor->id]);

    $this->order = Order::factory()->create([
        'buyer_id' => $this->buyer->id,
        'status'   => 'pending',
    ]);

    $this->subOrder = SubOrder::factory()->create([
        'order_id' => $this->order->id,
        'shop_id'  => $shop->id,
        'status'   => 'pending',
    ]);
});

test('buyer can cancel their own pending order', function (): void {
    app(CancelOrder::class)($this->order, $this->buyer);

    expect($this->order->fresh()->status)->toBe('cancelled');
});

test('cancelling order also cancels pending sub-orders', function (): void {
    app(CancelOrder::class)($this->order, $this->buyer);

    expect($this->subOrder->fresh()->status)->toBe('cancelled');
});

test('buyer cannot cancel an order in accepted status', function (): void {
    $this->order->update(['status' => 'accepted']);

    expect(fn () => app(CancelOrder::class)($this->order, $this->buyer))
        ->toThrow(\RuntimeException::class);
});

test('admin can cancel an accepted order', function (): void {
    $this->order->update(['status' => 'accepted']);
    $this->subOrder->update(['status' => 'accepted']);

    app(CancelOrder::class)($this->order, $this->admin);

    expect($this->order->fresh()->status)->toBe('cancelled');
});

test('stranger buyer cannot cancel someone else order', function (): void {
    $stranger = User::factory()->create(['role' => 'buyer']);

    expect(fn () => app(CancelOrder::class)($this->order, $stranger))
        ->toThrow(AuthorizationException::class);
});

test('cancellation with reason stores the reason', function (): void {
    app(CancelOrder::class)($this->order, $this->buyer, 'Changed my mind');

    expect($this->order->fresh()->cancellation_reason)->toBe('Changed my mind');
});
