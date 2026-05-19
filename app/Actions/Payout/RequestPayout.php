<?php

declare(strict_types=1);

namespace App\Actions\Payout;

use App\Models\Payout;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final class RequestPayout
{
    public function __invoke(
        User $user,
        Shop $shop,
        int $amountCents,
        string $bankName,
        string $bankAccountNumber,
        string $bankAccountName,
    ): Payout {
        if ($user->role !== 'vendor_owner' || $user->id !== $shop->owner_id) {
            throw new AuthorizationException('Only the shop owner may request a payout.');
        }

        if ($shop->status === 'suspended') {
            throw new \DomainException('Payouts are blocked while your shop is suspended.');
        }

        if ($shop->wallet->available_balance_cents < $amountCents) {
            throw new \DomainException('Insufficient available balance.');
        }

        return Payout::create([
            'shop_id'             => $shop->id,
            'amount_cents'        => $amountCents,
            'amount_currency'     => $shop->wallet->available_balance_currency ?? 'USD',
            'status'              => 'pending',
            'bank_name'           => $bankName,
            'bank_account_number' => $bankAccountNumber,
            'bank_account_name'   => $bankAccountName,
        ]);
    }
}
