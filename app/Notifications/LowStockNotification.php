<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ProductVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param list<array{name: string, sku: string, stock: int, threshold: int}> $variants */
    public function __construct(private readonly array $variants) {}

    /** @return list<string> */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $count = count($this->variants);

        $message = (new MailMessage)
            ->subject(__(':count product(s) are running low on stock', ['count' => $count]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('The following products in your shop are below their low stock threshold:'));

        foreach ($this->variants as $v) {
            $message->line("- **{$v['name']}** (SKU: {$v['sku']}) — {$v['stock']} left (threshold: {$v['threshold']})");
        }

        $message->line(__('Please restock soon to avoid missing sales.'));

        return $message;
    }
}
