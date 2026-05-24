<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FlashSale extends Model
{
    use BelongsToShop;

    protected $fillable = [
        'product_variant_id', 'shop_id', 'sale_price_cents', 'sale_price_currency',
        'quantity_limit', 'quantity_sold', 'starts_at', 'ends_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'sale_price_cents' => 'integer',
            'quantity_limit'   => 'integer',
            'quantity_sold'    => 'integer',
            'starts_at'        => 'datetime',
            'ends_at'          => 'datetime',
        ];
    }

    /** @return BelongsTo<ProductVariant, $this> */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->quantity_limit !== null && $this->quantity_sold >= $this->quantity_limit) {
            return false;
        }

        return $this->starts_at->isPast() && $this->ends_at->isFuture();
    }

    /** @param Builder<FlashSale> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->where(function (Builder $q): void {
                $q->whereNull('quantity_limit')
                    ->orWhereColumn('quantity_sold', '<', 'quantity_limit');
            });
    }
}
