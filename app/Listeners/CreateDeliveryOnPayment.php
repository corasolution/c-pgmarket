<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Contracts\DeliveryProvider;
use App\Events\Payment\PaymentReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Creates an Apollo delivery booking for each sub-order after payment is confirmed.
 * Runs as a queued listener so delivery failures never block wallet crediting.
 */
final class CreateDeliveryOnPayment implements ShouldQueue
{
    public function __construct(private readonly DeliveryProvider $deliveryProvider) {}

    public function handle(PaymentReceived $event): void
    {
        $order = $event->payment->order()->with('subOrders.shop')->first();

        if ($order === null) {
            return;
        }

        foreach ($order->subOrders as $subOrder) {
            $shop = $subOrder->shop;

            if ($shop === null) {
                Log::warning('CreateDeliveryOnPayment: subOrder has no shop', ['sub_order_id' => $subOrder->id]);
                continue;
            }

            if (! $shop->apollo_province_id) {
                Log::info('CreateDeliveryOnPayment: shop has no apollo_province_id, skipping', [
                    'shop_id'      => $shop->id,
                    'sub_order_id' => $subOrder->id,
                ]);
                continue;
            }

            if (! $order->apollo_receiver_province_id) {
                Log::info('CreateDeliveryOnPayment: order has no apollo_receiver_province_id, skipping', [
                    'order_id'     => $order->id,
                    'sub_order_id' => $subOrder->id,
                ]);
                continue;
            }

            try {
                $shipment = $this->deliveryProvider->createShipment($subOrder);

                Log::info('CreateDeliveryOnPayment: shipment created', [
                    'sub_order_id'    => $subOrder->id,
                    'tracking_number' => $shipment->tracking_number,
                ]);
            } catch (\Throwable $e) {
                Log::error('CreateDeliveryOnPayment: failed to create shipment', [
                    'sub_order_id' => $subOrder->id,
                    'error'        => $e->getMessage(),
                ]);
            }
        }
    }
}
