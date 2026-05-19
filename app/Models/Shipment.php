<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sub_order_id', 'provider', 'tracking_number', 'status',
        'shipping_fee_cents', 'shipping_fee_currency', 'provider_response',
        'picked_up_at', 'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'provider_response' => 'array',
            'shipping_fee_cents' => 'integer',
            'picked_up_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function subOrder(): BelongsTo
    {
        return $this->belongsTo(SubOrder::class);
    }
}
