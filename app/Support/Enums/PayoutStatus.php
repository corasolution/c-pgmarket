<?php

declare(strict_types=1);

namespace App\Support\Enums;

enum PayoutStatus: string
{
    case Pending   = 'pending';
    case Approved  = 'approved';
    case Paid      = 'paid';
    case Processed = 'processed';
    case Rejected  = 'rejected';
    case Failed    = 'failed';

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'Pending',
            self::Approved  => 'Approved',
            self::Paid      => 'Paid',
            self::Processed => 'Processed',
            self::Rejected  => 'Rejected',
            self::Failed    => 'Failed',
        };
    }
}
