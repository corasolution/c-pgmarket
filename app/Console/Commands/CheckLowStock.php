<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ProductVariant;
use App\Notifications\LowStockNotification;
use App\Services\TelegramService;
use Illuminate\Console\Command;

/**
 * Checks all stock-tracked products for low inventory and notifies
 * shop owners via email (and Telegram if configured).
 *
 * Designed to run daily via the scheduler.
 */
final class CheckLowStock extends Command
{
    protected $signature = 'stock:check-low';

    protected $description = 'Notify vendors about products with low stock';

    public function handle(TelegramService $telegram): int
    {
        $lowStockVariants = ProductVariant::query()
            ->whereHas('product', fn ($q) => $q->where('stock_track', true)->where('status', 'active'))
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('is_active', true)
            ->with('product.shop.owner')
            ->get();

        if ($lowStockVariants->isEmpty()) {
            $this->info('No low stock items found.');

            return self::SUCCESS;
        }

        // Group by shop
        $byShop = $lowStockVariants->groupBy(fn (ProductVariant $v) => $v->product->shop_id);

        $notified = 0;

        foreach ($byShop as $shopId => $variants) {
            $owner = $variants->first()->product->shop?->owner;
            if ($owner === null) {
                continue;
            }

            $items = $variants->map(fn (ProductVariant $v) => [
                'name'      => $v->product->name_i18n['en'] ?? $v->product->name_i18n['km'] ?? $v->sku,
                'sku'       => $v->sku,
                'stock'     => $v->stock_quantity,
                'threshold' => $v->low_stock_threshold,
            ])->values()->all();

            // Email notification
            $owner->notify(new LowStockNotification($items));

            // Telegram notification
            $chatId = $variants->first()->product->shop?->telegram_chat_id ?? '';
            if ($chatId !== '') {
                $lines = array_map(
                    fn (array $i) => "- {$i['name']} (SKU: {$i['sku']}): {$i['stock']} left",
                    $items,
                );
                $text = "⚠️ <b>Low Stock Alert</b>\n\n"
                    . implode("\n", $lines)
                    . "\n\nPlease restock soon.";

                $telegram->sendMessage($chatId, $text);
            }

            $notified++;
        }

        $this->info("Notified {$notified} shop(s) about low stock.");

        return self::SUCCESS;
    }
}
