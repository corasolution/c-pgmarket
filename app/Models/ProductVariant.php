<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'sku', 'options', 'price_cents', 'price_currency',
        'stock_quantity', 'image', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'price_cents' => 'integer',
            'stock_quantity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isInStock(): bool
    {
        if (! $this->product->stock_track) {
            return true;
        }

        return $this->stock_quantity > 0;
    }
}
