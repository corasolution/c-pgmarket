<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class VendorWallet extends Model
{
    use HasFactory;
    protected $fillable = [
        'shop_id', 'pending_balance_cents', 'pending_balance_currency',
        'available_balance_cents', 'available_balance_currency', 'lifetime_earned_cents',
    ];

    protected function casts(): array
    {
        return [
            'pending_balance_cents' => 'integer',
            'available_balance_cents' => 'integer',
            'lifetime_earned_cents' => 'integer',
        ];
    }

    /** @return BelongsTo<Shop, $this> */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /** @return HasMany<WalletTransaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
