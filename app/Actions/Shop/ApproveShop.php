<?php

declare(strict_types=1);

namespace App\Actions\Shop;

use App\Events\Shop\ShopApproved;
use App\Models\AuditLog;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final class ApproveShop
{
    public function __invoke(Shop $shop, User $admin): void
    {
        if ($admin->role !== 'admin') {
            throw new AuthorizationException('Only admins may approve shops.');
        }

        $before = ['status' => $shop->status];

        $shop->update([
            'status' => 'active',
            'approved_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'shop.approve',
            'auditable_type' => Shop::class,
            'auditable_id' => $shop->id,
            'before' => $before,
            'after' => ['status' => 'active'],
        ]);

        event(new ShopApproved($shop));
    }
}
