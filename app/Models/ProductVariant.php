<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'sku', 'options', 'price_cents', 'price_currency',
        'stock_quantity', 'low_stock_threshold', 'image', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'price_cents' => 'integer',
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return HasOne<FlashSale, $this> */
    public function activeFlashSale(): HasOne
    {
        return $this->hasOne(FlashSale::class)
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->where(function ($q) {
                $q->whereNull('quantity_limit')
                    ->orWhereColumn('quantity_sold', '<', 'quantity_limit');
            });
    }

    public function getEffectivePriceCents(): int
    {
        $flashSale = $this->activeFlashSale;

        if ($flashSale !== null && $flashSale->isActive()) {
            return $flashSale->sale_price_cents;
        }

        return $this->price_cents;
    }

    public function isInStock(): bool
    {
        if (! $this->product->stock_track) {
            return true;
        }

        return $this->stock_quantity > 0;
    }
}
