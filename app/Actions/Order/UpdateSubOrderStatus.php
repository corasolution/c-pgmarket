<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Models\SubOrder;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final class UpdateSubOrderStatus
{
    private const VENDOR_ALLOWED = ['accepted', 'packed', 'picked_up'];

    private const TRANSITIONS = [
        'pending' => ['accepted', 'cancelled'],
        'accepted' => ['packed', 'cancelled'],
        'packed' => ['picked_up'],
        'picked_up' => ['in_transit'],
        'in_transit' => ['delivered'],
        'delivered' => ['completed'],
    ];

    public function __invoke(User $actor, SubOrder $subOrder, string $status): SubOrder
    {
        $isAdmin = $actor->role === 'admin';
        $isVendor = in_array($actor->role, ['vendor_owner', 'vendor_staff'], strict: true)
            && $actor->shop_id === $subOrder->shop_id;

        if (! $isAdmin && ! $isVendor) {
            throw new AuthorizationException('You are not authorized to update this order.');
        }

        if (! $isAdmin && ! in_array($status, self::VENDOR_ALLOWED, strict: true)) {
            throw new AuthorizationException("Vendors cannot set status to '{$status}'.");
        }

        $allowed = self::TRANSITIONS[$subOrder->status] ?? [];
        if (! in_array($status, $allowed, strict: true)) {
            throw new \DomainException("Cannot transition from '{$subOrder->status}' to '{$status}'.");
        }

        $subOrder->update(['status' => $status]);

        return $subOrder;
    }
}
