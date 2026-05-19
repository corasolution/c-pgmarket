<?php

declare(strict_types=1);

namespace App\Actions\Shop;

use App\Events\Shop\ShopSuspended;
use App\Models\AuditLog;
use App\Models\Payout;
use App\Models\Shop;
use App\Models\SubOrder;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class SuspendShop
{
    public function __invoke(Shop $shop, User $admin, string $reason): void
    {
        if ($admin->role !== 'admin') {
            throw new AuthorizationException('Only admins may suspend shops.');
        }

        DB::transaction(function () use ($shop, $admin, $reason): void {
            $before = ['status' => $shop->status];

            $shop->update([
                'status'       => 'suspended',
                'suspended_at' => now(),
            ]);

            // Reject any pending payout requests
            Payout::where('shop_id', $shop->id)
                ->where('status', 'pending')
                ->update(['status' => 'rejected']);

            // Freeze in-flight sub-orders: prevent escrow release
            // Sub-orders in 'paid', 'accepted', 'packed' stay as-is but
            // delivered sub-orders get marked 'disputed' to block auto-release
            SubOrder::where('shop_id', $shop->id)
                ->where('status', 'delivered')
                ->update(['status' => 'disputed']);

            AuditLog::create([
                'user_id'        => $admin->id,
                'action'         => 'shop.suspend',
                'auditable_type' => Shop::class,
                'auditable_id'   => $shop->id,
                'before'         => $before,
                'after'          => ['status' => 'suspended', 'reason' => $reason],
            ]);

            event(new ShopSuspended($shop, $reason));
        });
    }
}
