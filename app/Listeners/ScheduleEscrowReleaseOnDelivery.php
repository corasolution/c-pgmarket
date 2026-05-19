<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Jobs\ReleaseEscrowJob;
use App\Models\SubOrder;
use Illuminate\Database\Eloquent\Model;

/**
 * Boots via model observer — when a SubOrder status becomes 'delivered',
 * schedule the escrow release job after PLATFORM_ESCROW_DAYS days.
 */
final class ScheduleEscrowReleaseOnDelivery
{
    public static function observe(): void
    {
        SubOrder::updated(function (Model $subOrder): void {
            /** @var SubOrder $subOrder */
            if ($subOrder->status === 'delivered' && $subOrder->wasChanged('status')) {
                $days = (int) config('platform.escrow_days', 7);
                ReleaseEscrowJob::dispatch($subOrder)->delay(now()->addDays($days));
            }
        });
    }
}
