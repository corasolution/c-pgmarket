<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Shop\ShopSuspended;
use App\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyVendorShopSuspended implements ShouldQueue
{
    public function handle(ShopSuspended $event): void
    {
        AuditLog::create([
            'user_id'        => $event->shop->owner_id,
            'action'         => 'shop.suspended',
            'auditable_type' => 'App\\Models\\Shop',
            'auditable_id'   => $event->shop->id,
            'after'          => ['status' => 'suspended', 'reason' => $event->reason],
        ]);

        $event->shop->owner?->notify(
            new \App\Notifications\ShopSuspendedNotification($event->shop, $event->reason)
        );
    }
}
