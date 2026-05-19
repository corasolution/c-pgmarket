<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Order\OrderCreated;
use App\Mail\OrderConfirmationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

final class SendBuyerOrderConfirmation implements ShouldQueue
{
    public function handle(OrderCreated $event): void
    {
        $order = $event->order->load('buyer', 'subOrders.items', 'subOrders.shop');
        $buyer = $order->buyer;

        if ($buyer?->email) {
            Mail::to($buyer->email)->queue(new OrderConfirmationMail($order));
        }
    }
}
