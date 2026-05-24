<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Coupon extends Model
{
    protected $fillable = [
        'code', 'type', 'value_cents', 'value_percent', 'min_order_cents',
        'max_discount_cents', 'max_uses', 'max_uses_per_user', 'times_used',
        'shop_id', 'starts_at', 'expires_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value_cents'       => 'integer',
            'value_percent'     => 'integer',
            'min_order_cents'   => 'integer',
            'max_discount_cents' => 'integer',
            'max_uses'          => 'integer',
            'max_uses_per_user' => 'integer',
            'times_used'        => 'integer',
            'is_active'         => 'boolean',
            'starts_at'         => 'datetime',
            'expires_at'        => 'datetime',
        ];
    }

    /** @return BelongsTo<Shop, $this> */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function isValid(int $orderTotalCents, int $userId): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at !== null && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->times_used >= $this->max_uses) {
            return false;
        }

        if ($orderTotalCents < $this->min_order_cents) {
            return false;
        }

        // Check per-user usage
        $userUsage = \DB::table('coupon_user')
            ->where('coupon_id', $this->id)
            ->where('user_id', $userId)
            ->count();

        if ($userUsage >= $this->max_uses_per_user) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(int $orderTotalCents): int
    {
        $discount = match ($this->type) {
            'percent' => (int) round($orderTotalCents * $this->value_percent / 100),
            'fixed' => $this->value_cents,
            'free_shipping' => 0,
            default => 0,
        };

        if ($this->max_discount_cents !== null && $discount > $this->max_discount_cents) {
            $discount = $this->max_discount_cents;
        }

        return min($discount, $orderTotalCents);
    }
}
