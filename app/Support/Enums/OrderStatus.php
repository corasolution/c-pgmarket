<?php

declare(strict_types=1);

namespace App\Support\Enums;

enum OrderStatus: string
{
    case Pending          = 'pending';
    case Paid             = 'paid';
    case Accepted         = 'accepted';
    case Packed           = 'packed';
    case PickedUp         = 'picked_up';
    case InTransit        = 'in_transit';
    case Delivered        = 'delivered';
    case Completed        = 'completed';
    case Cancelled        = 'cancelled';
    case RefundRequested  = 'refund_requested';
    case Refunded         = 'refunded';
    case Disputed         = 'disputed';

    public function label(): string
    {
        return match($this) {
            self::Pending         => 'Pending',
            self::Paid            => 'Paid',
            self::Accepted        => 'Accepted',
            self::Packed          => 'Packed',
            self::PickedUp        => 'Picked Up',
            self::InTransit       => 'In Transit',
            self::Delivered       => 'Delivered',
            self::Completed       => 'Completed',
            self::Cancelled       => 'Cancelled',
            self::RefundRequested => 'Refund Requested',
            self::Refunded        => 'Refunded',
            self::Disputed        => 'Disputed',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::Completed,
            self::Cancelled,
            self::Refunded,
        ], true);
    }
}
