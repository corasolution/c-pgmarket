<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Shop;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ShopSuspendedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Shop $shop,
        private readonly string $reason,
    ) {}

    /** @return list<string> */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Your shop has been suspended'))
            ->greeting(__('Hello :name', ['name' => $notifiable->name]))
            ->line(__('Your shop ":shop" has been suspended.', ['shop' => $this->shop->name]))
            ->line(__('Reason: :reason', ['reason' => $this->reason]))
            ->line(__('Please contact support if you believe this is a mistake.'));
    }
}
