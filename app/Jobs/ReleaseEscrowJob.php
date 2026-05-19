<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Payment\ReleaseEscrow;
use App\Models\Dispute;
use App\Models\SubOrder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Dispatched when a SubOrder reaches 'delivered'.
 * Delayed by PLATFORM_ESCROW_DAYS (default 7) days.
 * Moves pending_balance → available_balance minus commission.
 *
 * Skips release if:
 *   - SubOrder is no longer in delivered/completed status (e.g. refunded, cancelled)
 *   - An open dispute exists for any item in this SubOrder
 */
final class ReleaseEscrowJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly SubOrder $subOrder) {}

    public function handle(ReleaseEscrow $releaseEscrow): void
    {
        $this->subOrder->refresh();

        if (! in_array($this->subOrder->status, ['delivered', 'completed'], strict: true)) {
            return;
        }

        // Check for open disputes on any item in this SubOrder
        $hasOpenDispute = Dispute::where('shop_id', $this->subOrder->shop_id)
            ->whereIn('order_item_id', $this->subOrder->items()->pluck('id'))
            ->whereIn('status', ['open', 'under_review'])
            ->exists();

        if ($hasOpenDispute) {
            Log::info('Escrow release deferred — open dispute exists', [
                'sub_order_id' => $this->subOrder->id,
            ]);

            // Re-dispatch with 24h delay to check again
            self::dispatch($this->subOrder)->delay(now()->addDay());

            return;
        }

        $releaseEscrow($this->subOrder);
    }
}
