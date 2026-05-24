<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SubOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OrderCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param 'buyer'|'vendor' $recipientType
     */
    public function __construct(
        private readonly SubOrder $subOrder,
        private readonly string $recipientType,
        private readonly string $reason = '',
    ) {}

    /** @return list<string> */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $ref = $this->subOrder->order?->reference ?? (string) $this->subOrder->order_id;

        $message = (new MailMessage)
            ->subject(__('Order #:ref has been cancelled', ['ref' => $ref]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]));

        if ($this->recipientType === 'buyer') {
            $message->line(__('Your order #:ref has been cancelled.', ['ref' => $ref]));
            if ($this->reason !== '') {
                $message->line(__('Reason: :reason', ['reason' => $this->reason]));
            }
            $message->line(__('If you were charged, a refund will be processed within a few business days.'));
        } else {
            $shop = $this->subOrder->shop?->name ?? 'your shop';
            $message->line(__('An order (#:ref) for :shop has been cancelled.', [
                'ref'  => $ref,
                'shop' => $shop,
            ]));
            if ($this->reason !== '') {
                $message->line(__('Reason: :reason', ['reason' => $this->reason]));
            }
        }

        return $message->action(__('View Orders'), url('/orders'));
    }
}
