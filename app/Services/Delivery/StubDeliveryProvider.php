<?php

declare(strict_types=1);

namespace App\Services\Delivery;

use App\Contracts\DeliveryProvider;
use App\Models\Shipment;
use App\Models\SubOrder;

final class StubDeliveryProvider implements DeliveryProvider
{
    public function createShipment(SubOrder $subOrder): Shipment
    {
        return Shipment::create([
            'sub_order_id' => $subOrder->id,
            'provider' => 'stub',
            'tracking_number' => 'STUB-'.strtoupper(uniqid()),
            'status' => 'pending',
            'shipping_fee_cents' => 0,
        ]);
    }

    /** @return array<string, mixed> */
    public function getRate(SubOrder $subOrder): array
    {
        return ['fee_cents' => 0, 'currency' => 'USD', 'provider' => 'stub'];
    }

    /** @return array<string, mixed> */
    public function trackShipment(Shipment $shipment): array
    {
        return ['status' => $shipment->status, 'events' => []];
    }

    public function cancelShipment(Shipment $shipment): bool
    {
        return true;
    }

    /** @param array<string, mixed> $payload */
    public function handleWebhook(array $payload): void {}
}
