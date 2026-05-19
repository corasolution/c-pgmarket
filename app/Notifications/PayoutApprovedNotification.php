<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PayoutApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Payout $payout) {}

    /** @return list<string> */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $amount = number_format($this->payout->amount_cents / 100, 2);
        $currency = $this->payout->currency ?? 'USD';

        return (new MailMessage)
            ->subject(__('Your payout has been approved'))
            ->greeting(__('Hello :name', ['name' => $notifiable->name]))
            ->line(__('Your payout of :amount :currency has been approved and will be processed shortly.', [
                'amount'   => $amount,
                'currency' => $currency,
            ]))
            ->line(__('Payout ID: :id', ['id' => $this->payout->id]));
    }
}
