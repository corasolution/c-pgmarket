<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Order\OrderCreated;
use App\Models\User;
use App\Notifications\NewSubOrderNotification;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyShopsOrderCreated implements ShouldQueue
{
    public function __construct(private readonly TelegramService $telegram) {}

    public function handle(OrderCreated $event): void
    {
        $order = $event->order->load('subOrders.shop.owner', 'subOrders.items');

        foreach ($order->subOrders as $subOrder) {
            $owner = $subOrder->shop?->owner;

            if ($owner instanceof User) {
                // Email notification
                $owner->notify(new NewSubOrderNotification($subOrder));

                // Telegram notification
                $chatId = $subOrder->shop->telegram_chat_id ?? '';
                if ($chatId !== '') {
                    $amount = number_format($subOrder->subtotal_cents / 100, 2);
                    $currency = $subOrder->subtotal_currency ?? 'USD';
                    $ref = $order->reference;
                    $itemCount = $subOrder->items->count();

                    $text = "🛒 <b>New Order Received!</b>\n\n"
                        . "Order: <b>#{$ref}</b>\n"
                        . "Items: {$itemCount}\n"
                        . "Amount: <b>{$amount} {$currency}</b>\n\n"
                        . "Please accept it promptly in your vendor panel.";

                    $this->telegram->sendMessage($chatId, $text);
                }
            }
        }
    }
}
