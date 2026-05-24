<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\BackInStockSubscription;
use App\Notifications\BackInStockNotification;
use Illuminate\Console\Command;

/**
 * Finds subscriptions where the variant is now in stock (stock_quantity > 0)
 * and notifies the user. Marks as notified to prevent duplicates.
 *
 * Runs every 30 minutes via the scheduler.
 */
final class NotifyBackInStock extends Command
{
    protected $signature = 'stock:notify-back-in-stock';

    protected $description = 'Notify users when their wishlisted out-of-stock items are restocked';

    public function handle(): int
    {
        $subscriptions = BackInStockSubscription::query()
            ->whereNull('notified_at')
            ->with(['user', 'variant.product'])
            ->get();

        $notified = 0;

        foreach ($subscriptions as $subscription) {
            $variant = $subscription->variant;

            if ($variant === null || $variant->stock_quantity <= 0) {
                continue;
            }

            // Only notify for stock-tracked products that are back in stock
            if ($variant->product?->stock_track === false) {
                continue;
            }

            $subscription->user->notify(new BackInStockNotification($variant));
            $subscription->update(['notified_at' => now()]);
            $notified++;
        }

        $this->info("Sent {$notified} back-in-stock notification(s).");

        return self::SUCCESS;
    }
}
