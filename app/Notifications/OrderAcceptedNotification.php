<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SubOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OrderAcceptedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly SubOrder $subOrder) {}

    /** @return list<string> */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $ref = $this->subOrder->order?->reference ?? (string) $this->subOrder->order_id;
        $shop = $this->subOrder->shop?->name ?? 'the vendor';

        return (new MailMessage)
            ->subject(__('Your order has been accepted — #:ref', ['ref' => $ref]))
            ->greeting(__('Hello :name!', ['name' => $notifiable->name]))
            ->line(__(':shop has accepted your order #:ref and is now preparing it.', [
                'shop' => $shop,
                'ref'  => $ref,
            ]))
            ->line(__('We\'ll notify you once it\'s packed and ready for shipping.'))
            ->action(__('View Order'), url('/orders/' . $this->subOrder->order_id));
    }
}
