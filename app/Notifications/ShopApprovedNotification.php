<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Shop;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ShopApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Shop $shop) {}

    /** @return list<string> */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Your shop has been approved!'))
            ->greeting(__('Hello :name', ['name' => $notifiable->name]))
            ->line(__('Great news! Your shop ":shop" has been approved and is now active.', ['shop' => $this->shop->name]))
            ->action(__('View your shop'), url('/shops/'.$this->shop->slug))
            ->line(__('You can now start listing products and receive orders.'));
    }
}
