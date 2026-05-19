<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\Shop;
use App\Models\Shipment;
use App\Models\SubOrder;
use App\Models\User;
use App\Services\Delivery\StubDeliveryProvider;

beforeEach(function (): void {
    $vendor = User::factory()->create(['role' => 'vendor_owner']);
    $this->shop = Shop::factory()->create(['owner_id' => $vendor->id]);

    $buyer = User::factory()->create(['role' => 'buyer']);
    $order = Order::factory()->create(['buyer_id' => $buyer->id]);

    $this->subOrder = SubOrder::factory()->create([
        'order_id' => $order->id,
        'shop_id'  => $this->shop->id,
        'status'   => 'picked_up',
    ]);

    $this->shipment = Shipment::factory()->create([
        'sub_order_id'    => $this->subOrder->id,
        'provider'        => 'stub',
        'tracking_number' => 'STUB-TEST-001',
        'status'          => 'picked_up',
    ]);

    $this->provider = app(StubDeliveryProvider::class);
});

test('stub provider handleWebhook accepts valid payload without throwing', function (): void {
    expect(fn () => $this->provider->handleWebhook([
        'tracking_number' => 'STUB-TEST-001',
        'status'          => 'delivered',
    ]))->not->toThrow(\Throwable::class);
});

test('stub provider handleWebhook ignores unknown tracking number gracefully', function (): void {
    expect(fn () => $this->provider->handleWebhook([
        'tracking_number' => 'UNKNOWN-999',
        'status'          => 'delivered',
    ]))->not->toThrow(\Throwable::class);
});

test('stub provider getRate returns zero fee', function (): void {
    $rate = $this->provider->getRate($this->subOrder);

    expect($rate['fee_cents'])->toBe(0)
        ->and($rate['provider'])->toBe('stub');
});

test('stub provider createShipment returns a Shipment model', function (): void {
    $shipment = $this->provider->createShipment($this->subOrder);

    expect($shipment)->toBeInstanceOf(Shipment::class)
        ->and($shipment->provider)->toBe('stub');
});
