<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SubOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class NewSubOrderNotification extends Notification implements ShouldQueue
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
        $amount = number_format($this->subOrder->subtotal_cents / 100, 2);
        $currency = $this->subOrder->subtotal_currency ?? 'USD';

        return (new MailMessage)
            ->subject(__('New order received!'))
            ->greeting(__('Hello :name', ['name' => $notifiable->name]))
            ->line(__('You have received a new order (#:ref) totalling :amount :currency.', [
                'ref'      => $this->subOrder->order?->reference ?? $this->subOrder->id,
                'amount'   => $amount,
                'currency' => $currency,
            ]))
            ->line(__('Please accept it promptly to keep your shop rating high.'));
    }
}
