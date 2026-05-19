<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Shop\ShopApproved;
use App\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyVendorShopApproved implements ShouldQueue
{
    public function handle(ShopApproved $event): void
    {
        AuditLog::create([
            'user_id'        => $event->shop->owner_id,
            'action'         => 'shop.approved',
            'auditable_type' => 'App\\Models\\Shop',
            'auditable_id'   => $event->shop->id,
            'after'          => ['status' => 'active'],
        ]);

        $event->shop->owner?->notify(
            new \App\Notifications\ShopApprovedNotification($event->shop)
        );
    }
}
