<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Shipment;
use App\Models\SubOrder;

interface DeliveryProvider
{
    public function createShipment(SubOrder $subOrder): Shipment;

    /** @return array<string, mixed> */
    public function getRate(SubOrder $subOrder): array;

    /** @return array<string, mixed> */
    public function trackShipment(Shipment $shipment): array;

    public function cancelShipment(Shipment $shipment): bool;

    /** @param array<string, mixed> $payload */
    public function handleWebhook(array $payload): void;
}
