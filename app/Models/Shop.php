<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Shop extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id', 'name', 'slug', 'description_i18n', 'logo', 'banner',
        'status', 'commission_percent', 'currency', 'phone', 'email', 'address',
        'facebook_page', 'telegram', 'telegram_chat_id',
        'apollo_province_id', 'apollo_district_id',
    ];

    protected function casts(): array
    {
        return [
            'description_i18n' => 'array',
            'address' => 'array',
            'approved_at' => 'datetime',
            'suspended_at' => 'datetime',
            'commission_percent'   => 'integer',
            'apollo_province_id'   => 'integer',
            'apollo_district_id'   => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return HasOne<ShopVerification, $this> */
    public function verification(): HasOne
    {
        return $this->hasOne(ShopVerification::class);
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** @return HasOne<VendorWallet, $this> */
    public function wallet(): HasOne
    {
        return $this->hasOne(VendorWallet::class);
    }

    /** @return HasMany<SubOrder, $this> */
    public function subOrders(): HasMany
    {
        return $this->hasMany(SubOrder::class);
    }

    /** @return HasMany<Dispute, $this> */
    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class);
    }

    /** @return HasMany<Conversation, $this> */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /** @return HasMany<Payout, $this> */
    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
