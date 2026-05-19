<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_wallet_id', 'sub_order_id', 'type', 'reason',
        'amount_cents', 'amount_currency', 'balance_after_cents', 'reference', 'note',
    ];

    protected function casts(): array
    {
        return ['amount_cents' => 'integer', 'balance_after_cents' => 'integer'];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(VendorWallet::class, 'vendor_wallet_id');
    }
}
