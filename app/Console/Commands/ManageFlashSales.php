<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\FlashSale;
use Illuminate\Console\Command;

/**
 * Activates scheduled flash sales that have started and
 * completes active flash sales that have expired.
 *
 * Runs every minute via the scheduler.
 */
final class ManageFlashSales extends Command
{
    protected $signature = 'flash-sales:manage';

    protected $description = 'Activate scheduled flash sales and complete expired ones';

    public function handle(): int
    {
        // Activate scheduled sales that should be live now
        $activated = FlashSale::where('status', 'scheduled')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->update(['status' => 'active']);

        // Complete expired active sales
        $completed = FlashSale::where('status', 'active')
            ->where('ends_at', '<=', now())
            ->update(['status' => 'completed']);

        // Complete sold-out sales
        $soldOut = FlashSale::where('status', 'active')
            ->whereNotNull('quantity_limit')
            ->whereColumn('quantity_sold', '>=', 'quantity_limit')
            ->update(['status' => 'completed']);

        $this->info("Activated: {$activated}, Completed: {$completed}, Sold out: {$soldOut}");

        return self::SUCCESS;
    }
}
