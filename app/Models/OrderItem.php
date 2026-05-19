<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sub_order_id', 'product_variant_id', 'product_name_snapshot', 'variant_sku_snapshot',
        'image_snapshot', 'options_snapshot', 'quantity', 'unit_price_cents', 'unit_price_currency',
    ];

    protected function casts(): array
    {
        return [
            'options_snapshot' => 'array',
            'quantity' => 'integer',
            'unit_price_cents' => 'integer',
        ];
    }

    public function subOrder(): BelongsTo
    {
        return $this->belongsTo(SubOrder::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function dispute(): HasOne
    {
        return $this->hasOne(Dispute::class);
    }
}
