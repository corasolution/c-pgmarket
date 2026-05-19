<?php

declare(strict_types=1);

namespace App\Actions\Dispute;

use App\Models\Dispute;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final class OpenDispute
{
    public function __invoke(
        User $buyer,
        OrderItem $orderItem,
        string $reason,
        string $description,
    ): Dispute {
        $subOrder = $orderItem->subOrder()->with('order')->firstOrFail();
        $order    = $subOrder->order;

        if ($buyer->id !== $order->buyer_id) {
            throw new AuthorizationException('Only the buyer may open a dispute on this order item.');
        }

        if (! in_array($subOrder->status, ['delivered', 'completed'], strict: true)) {
            throw new \DomainException('Disputes can only be opened for delivered or completed sub-orders.');
        }

        if ($orderItem->dispute()->exists()) {
            throw new \DomainException('A dispute has already been opened for this item.');
        }

        return Dispute::create([
            'order_item_id' => $orderItem->id,
            'buyer_id'      => $buyer->id,
            'shop_id'       => $subOrder->shop_id,
            'reason'        => $reason,
            'description'   => $description,
            'status'        => 'open',
        ]);
    }
}
