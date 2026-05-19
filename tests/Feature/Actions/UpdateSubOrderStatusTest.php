<?php

declare(strict_types=1);

use App\Actions\Order\UpdateSubOrderStatus;
use App\Models\Order;
use App\Models\Shop;
use App\Models\SubOrder;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function (): void {
    $this->vendor = User::factory()->create(['role' => 'vendor_owner']);
    $this->shop = Shop::factory()->create(['owner_id' => $this->vendor->id, 'status' => 'active']);
    $this->vendor->update(['shop_id' => $this->shop->id]);

    $buyer = User::factory()->create(['role' => 'buyer']);
    $order = Order::factory()->create(['buyer_id' => $buyer->id, 'status' => 'paid']);

    $this->subOrder = SubOrder::factory()->create([
        'order_id' => $order->id,
        'shop_id'  => $this->shop->id,
        'status'   => 'pending',
    ]);

    $this->admin = User::factory()->create(['role' => 'admin']);
});

test('vendor can accept their own sub-order', function (): void {
    app(UpdateSubOrderStatus::class)(
        subOrder: $this->subOrder,
        actor: $this->vendor,
        status: 'accepted',
    );

    expect($this->subOrder->fresh()->status)->toBe('accepted');
});

test('vendor can advance status through vendor-allowed states', function (): void {
    $this->subOrder->update(['status' => 'accepted']);

    app(UpdateSubOrderStatus::class)(
        subOrder: $this->subOrder,
        actor: $this->vendor,
        status: 'packed',
    );

    expect($this->subOrder->fresh()->status)->toBe('packed');
});

test('vendor cannot set admin-only status like delivered', function (): void {
    expect(fn () => app(UpdateSubOrderStatus::class)(
        subOrder: $this->subOrder,
        actor: $this->vendor,
        status: 'delivered',
    ))->toThrow(AuthorizationException::class);
});

test('admin can set any status', function (): void {
    app(UpdateSubOrderStatus::class)(
        subOrder: $this->subOrder,
        actor: $this->admin,
        status: 'delivered',
    );

    expect($this->subOrder->fresh()->status)->toBe('delivered');
});

test('vendor of different shop cannot update sub-order', function (): void {
    $otherVendor = User::factory()->create(['role' => 'vendor_owner']);
    Shop::factory()->create(['owner_id' => $otherVendor->id]);

    expect(fn () => app(UpdateSubOrderStatus::class)(
        subOrder: $this->subOrder,
        actor: $otherVendor,
        status: 'accepted',
    ))->toThrow(AuthorizationException::class);
});
