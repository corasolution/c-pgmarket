<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Order\OrderCreated;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyShopsOrderCreated implements ShouldQueue
{
    public function handle(OrderCreated $event): void
    {
        $order = $event->order->load('subOrders.shop.owner');

        foreach ($order->subOrders as $subOrder) {
            $owner = $subOrder->shop?->owner;

            if ($owner instanceof User) {
                $owner->notify(
                    new \App\Notifications\NewSubOrderNotification($subOrder)
                );
            }
        }
    }
}
