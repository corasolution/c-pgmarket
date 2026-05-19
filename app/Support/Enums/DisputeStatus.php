<?php

declare(strict_types=1);

namespace App\Support\Enums;

enum DisputeStatus: string
{
    case Open       = 'open';
    case InReview   = 'in_review';
    case Resolved   = 'resolved';
    case Closed     = 'closed';

    public function label(): string
    {
        return match($this) {
            self::Open     => 'Open',
            self::InReview => 'In Review',
            self::Resolved => 'Resolved',
            self::Closed   => 'Closed',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Resolved, self::Closed], true);
    }
}
