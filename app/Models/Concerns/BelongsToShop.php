<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Global scope ensuring vendor queries are isolated to their own shop.
 * Apply to every model that has a shop_id column.
 */
trait BelongsToShop
{
    public static function bootBelongsToShop(): void
    {
        static::addGlobalScope('shop', function (Builder $builder): void {
            if (app()->runningInConsole() && ! app()->runningUnitTests()) {
                return;
            }

            /** @var User|null $user */
            $user = auth()->user();

            if ($user === null || $user->role === 'admin') {
                return;
            }

            if (in_array($user->role, ['vendor_owner', 'vendor_staff'], strict: true)) {
                $shopId = $user->ownedShop?->id ?? $user->staffShop?->id;
                if ($shopId !== null) {
                    $builder->where($builder->qualifyColumn('shop_id'), $shopId);
                }
            }
        });
    }

    /** @return BelongsTo<Shop, $this> */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
