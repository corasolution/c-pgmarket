<?php

declare(strict_types=1);

namespace App\Actions\Shop;

use App\Models\Shop;
use App\Models\User;
use App\Models\VendorWallet;
use App\Support\Enums\UserRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateShop
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(User $owner, array $data): Shop
    {
        if (! in_array($owner->role, [UserRole::VendorOwner->value, UserRole::Admin->value], strict: true)) {
            throw new AuthorizationException('Only vendor owners may create a shop.');
        }

        if (Shop::where('owner_id', $owner->id)->exists()) {
            throw new \RuntimeException('This user already owns a shop.');
        }

        return DB::transaction(function () use ($owner, $data): Shop {
            $shop = Shop::create([
                'owner_id'           => $owner->id,
                'name'               => $data['name'],
                'slug'               => Str::slug($data['name']).'-'.Str::lower(Str::random(6)),
                'description_i18n'   => $data['description_i18n'] ?? [],
                'status'             => 'draft',
                'commission_percent' => (int) config('platform.commission_percent', 8),
                'currency'           => $data['currency'] ?? config('platform.currency', 'USD'),
                'phone'              => $data['phone'] ?? null,
                'email'              => $data['email'] ?? null,
            ]);

            VendorWallet::create([
                'shop_id'                    => $shop->id,
                'available_balance_cents'    => 0,
                'available_balance_currency' => 'USD',
                'pending_balance_cents'      => 0,
                'pending_balance_currency'   => 'USD',
                'lifetime_earned_cents'      => 0,
            ]);

            $owner->update(['shop_id' => $shop->id]);

            return $shop;
        });
    }
}
