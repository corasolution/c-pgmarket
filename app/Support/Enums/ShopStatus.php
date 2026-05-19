<?php

declare(strict_types=1);

namespace App\Support\Enums;

enum ShopStatus: string
{
    case Draft     = 'draft';
    case Submitted = 'submitted';
    case Approved  = 'approved';
    case Active    = 'active';
    case Suspended = 'suspended';
    case Rejected  = 'rejected';

    public function label(): string
    {
        return match($this) {
            self::Draft     => 'Draft',
            self::Submitted => 'Submitted',
            self::Approved  => 'Approved',
            self::Active    => 'Active',
            self::Suspended => 'Suspended',
            self::Rejected  => 'Rejected',
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return match($this) {
            self::Draft     => $next === self::Submitted,
            self::Submitted => in_array($next, [self::Approved, self::Rejected], true),
            self::Approved  => $next === self::Active,
            self::Active    => $next === self::Suspended,
            self::Suspended => $next === self::Active,
            self::Rejected  => false,
        };
    }
}
