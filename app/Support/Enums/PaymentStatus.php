<?php

declare(strict_types=1);

namespace App\Support\Enums;

enum PaymentStatus: string
{
    case Pending           = 'pending';
    case Paid              = 'paid';
    case Failed            = 'failed';
    case Refunded          = 'refunded';
    case PartiallyRefunded = 'partially_refunded';
    case Expired           = 'expired';

    public function label(): string
    {
        return match($this) {
            self::Pending           => 'Pending',
            self::Paid              => 'Paid',
            self::Failed            => 'Failed',
            self::Refunded          => 'Refunded',
            self::PartiallyRefunded => 'Partially Refunded',
            self::Expired           => 'Expired',
        };
    }
}
