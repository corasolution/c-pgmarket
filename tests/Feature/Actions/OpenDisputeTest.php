<?php

declare(strict_types=1);

use App\Actions\Dispute\OpenDispute;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shop;
use App\Models\SubOrder;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function (): void {
    $this->buyer = User::factory()->create(['role' => 'buyer']);

    $vendor = User::factory()->create(['role' => 'vendor_owner']);
    $this->shop = Shop::factory()->create(['owner_id' => $vendor->id]);

    $this->order = Order::factory()->create([
        'buyer_id' => $this->buyer->id,
        'status'   => 'delivered',
    ]);

    $this->subOrder = SubOrder::factory()->create([
        'order_id' => $this->order->id,
        'shop_id'  => $this->shop->id,
        'status'   => 'delivered',
    ]);

    $this->orderItem = OrderItem::factory()->create([
        'sub_order_id' => $this->subOrder->id,
    ]);
});

test('buyer can open a dispute on their delivered order item', function (): void {
    $dispute = app(OpenDispute::class)(
        buyer: $this->buyer,
        orderItem: $this->orderItem,
        reason: 'Item not as described',
        description: 'The product arrived damaged.',
    );

    expect($dispute)->toBeInstanceOf(Dispute::class)
        ->and($dispute->status)->toBe('open')
        ->and($dispute->order_item_id)->toBe($this->orderItem->id)
        ->and($dispute->shop_id)->toBe($this->shop->id);
});

test('buyer cannot dispute an item whose sub-order is not delivered', function (): void {
    $this->subOrder->update(['status' => 'pending']);

    expect(fn () => app(OpenDispute::class)(
        buyer: $this->buyer,
        orderItem: $this->orderItem,
        reason: 'Missing item',
        description: 'Parcel was empty.',
    ))->toThrow(\DomainException::class);
});

test('a different buyer cannot dispute someone else order item', function (): void {
    $stranger = User::factory()->create(['role' => 'buyer']);

    expect(fn () => app(OpenDispute::class)(
        buyer: $stranger,
        orderItem: $this->orderItem,
        reason: 'Wrong address',
        description: 'Order was not mine.',
    ))->toThrow(AuthorizationException::class);
});

test('cannot open a duplicate dispute on the same item', function (): void {
    app(OpenDispute::class)(
        buyer: $this->buyer,
        orderItem: $this->orderItem,
        reason: 'First dispute',
        description: 'Item broken.',
    );

    expect(fn () => app(OpenDispute::class)(
        buyer: $this->buyer,
        orderItem: $this->orderItem,
        reason: 'Second dispute',
        description: 'Still broken.',
    ))->toThrow(\DomainException::class);
});
