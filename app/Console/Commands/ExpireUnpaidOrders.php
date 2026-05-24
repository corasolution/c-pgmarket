<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Cancels orders that remain in 'pending' status longer than the configured
 * expiry window (default 30 minutes). Restores stock for tracked products.
 *
 * Designed to run every 5 minutes via the scheduler.
 */
final class ExpireUnpaidOrders extends Command
{
    protected $signature = 'orders:expire-unpaid {--minutes=30 : Minutes before a pending order expires}';

    protected $description = 'Cancel unpaid orders older than the configured expiry window and restore stock';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');

        $expiredOrders = Order::where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes($minutes))
            ->with('subOrders.items.variant.product')
            ->get();

        if ($expiredOrders->isEmpty()) {
            $this->info('No expired orders found.');

            return self::SUCCESS;
        }

        $count = 0;

        foreach ($expiredOrders as $order) {
            DB::transaction(function () use ($order): void {
                $order->update([
                    'status'              => 'cancelled',
                    'cancellation_reason' => 'Payment not received — auto-expired.',
                ]);

                foreach ($order->subOrders as $subOrder) {
                    if ($subOrder->status === 'cancelled') {
                        continue;
                    }

                    $subOrder->update(['status' => 'cancelled']);

                    // Restore stock for tracked products
                    foreach ($subOrder->items as $orderItem) {
                        if ($orderItem->variant?->product?->stock_track) {
                            ProductVariant::where('id', $orderItem->product_variant_id)
                                ->increment('stock_quantity', $orderItem->quantity);
                        }
                    }
                }
            });

            $count++;
        }

        $this->info("Expired {$count} unpaid order(s).");
        Log::info("ExpireUnpaidOrders: cancelled {$count} order(s) older than {$minutes} minutes.");

        return self::SUCCESS;
    }
}
