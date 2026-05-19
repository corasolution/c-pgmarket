<?php

declare(strict_types=1);

namespace App\Support\Enums;

enum WalletTransactionType: string
{
    case Credit         = 'credit';
    case Debit          = 'debit';
    case Hold           = 'hold';
    case Release        = 'release';
    case CommissionFee  = 'commission_fee';
    case Payout         = 'payout';
    case Refund         = 'refund';

    public function label(): string
    {
        return match($this) {
            self::Credit        => 'Credit',
            self::Debit         => 'Debit',
            self::Hold          => 'Hold',
            self::Release       => 'Release',
            self::CommissionFee => 'Commission Fee',
            self::Payout        => 'Payout',
            self::Refund        => 'Refund',
        };
    }

    public function isDebit(): bool
    {
        return in_array($this, [self::Debit, self::CommissionFee, self::Payout, self::Refund], true);
    }
}
