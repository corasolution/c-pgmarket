<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ProductVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class BackInStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly ProductVariant $variant) {}

    /** @return list<string> */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $name = $this->variant->product?->name_i18n['en']
            ?? $this->variant->product?->name_i18n['km']
            ?? $this->variant->sku;
        $slug = $this->variant->product?->slug ?? '';

        return (new MailMessage)
            ->subject(__(':name is back in stock!', ['name' => $name]))
            ->greeting(__('Hello :name!', ['name' => $notifiable->name]))
            ->line(__('Great news! :product is back in stock on PG Market.', ['product' => $name]))
            ->line(__('Hurry — it may sell out again!'))
            ->action(__('View Product'), url("/products/{$slug}"));
    }
}
