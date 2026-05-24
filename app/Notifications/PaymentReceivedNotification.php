<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PaymentReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Order $order) {}

    /** @return list<string> */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $total = number_format($this->order->total_cents / 100, 2);
        $currency = $this->order->total_currency ?? 'USD';

        return (new MailMessage)
            ->subject(__('Payment confirmed — Order #:ref', ['ref' => $this->order->reference]))
            ->greeting(__('Hello :name!', ['name' => $notifiable->name]))
            ->line(__('We\'ve received your payment of :amount :currency for order #:ref.', [
                'amount'   => $total,
                'currency' => $currency,
                'ref'      => $this->order->reference,
            ]))
            ->line(__('The vendors have been notified and will start processing your order shortly.'))
            ->action(__('View Order'), url('/orders/' . $this->order->id));
    }
}
