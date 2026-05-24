<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SubOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OrderDeliveredNotification extends Notification implements ShouldQueue
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
            ->subject(__('Your order has been delivered — #:ref', ['ref' => $ref]))
            ->greeting(__('Hello :name!', ['name' => $notifiable->name]))
            ->line(__('Your order #:ref from :shop has been delivered. We hope you love it!', [
                'shop' => $shop,
                'ref'  => $ref,
            ]))
            ->line(__('If you have any issues, you can open a dispute within 7 days.'))
            ->action(__('View Order'), url('/orders/' . $this->subOrder->order_id));
    }
}
