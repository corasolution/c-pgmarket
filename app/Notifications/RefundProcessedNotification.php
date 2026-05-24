<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class RefundProcessedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Payment $payment,
        private readonly int $amountCents,
    ) {}

    /** @return list<string> */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $ref = $this->payment->order?->reference ?? (string) $this->payment->order_id;
        $amount = number_format($this->amountCents / 100, 2);
        $currency = $this->payment->amount_currency ?? 'USD';

        return (new MailMessage)
            ->subject(__('Refund processed — Order #:ref', ['ref' => $ref]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('A refund of :amount :currency has been processed for your order #:ref.', [
                'amount'   => $amount,
                'currency' => $currency,
                'ref'      => $ref,
            ]))
            ->line(__('The refund should appear in your account within 3–5 business days.'))
            ->action(__('View Order'), url('/orders/' . $this->payment->order_id));
    }
}
