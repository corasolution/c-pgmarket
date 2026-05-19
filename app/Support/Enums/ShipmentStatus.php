<?php

declare(strict_types=1);

namespace App\Support\Enums;

enum ShipmentStatus: string
{
    case Pending    = 'pending';
    case PickedUp   = 'picked_up';
    case InTransit  = 'in_transit';
    case Delivered  = 'delivered';
    case Failed     = 'failed';
    case Cancelled  = 'cancelled';
    case Returned   = 'returned';

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'Pending',
            self::PickedUp  => 'Picked Up',
            self::InTransit => 'In Transit',
            self::Delivered => 'Delivered',
            self::Failed    => 'Failed',
            self::Cancelled => 'Cancelled',
            self::Returned  => 'Returned',
        };
    }
}
