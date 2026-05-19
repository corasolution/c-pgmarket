<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Models\Shop;
use App\Models\SubOrder;
use App\Models\VendorWallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Move funds from pending_balance → available_balance minus platform commission.
 * Called after delivery + 7-day buyer confirmation window expires.
 *
 * Creates two WalletTransactions:
 *   1. escrow_release  — gross amount moved from pending to available
 *   2. commission_fee  — platform commission deducted from available
 */
final class ReleaseEscrow
{
    public function __invoke(SubOrder $subOrder): WalletTransaction
    {
        return DB::transaction(function () use ($subOrder): WalletTransaction {
            $existing = WalletTransaction::where('sub_order_id', $subOrder->id)
                ->where('reason', 'escrow_release')
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $wallet = VendorWallet::where('shop_id', $subOrder->shop_id)->lockForUpdate()->firstOrFail();

            $commissionPct = Shop::find($subOrder->shop_id)?->commission_percent
                ?? (int) config('platform.commission_percent', 8);

            $gross = $subOrder->subtotal_cents;
            $commission = (int) round($gross * $commissionPct / 100);
            $net = $gross - $commission;

            // Move gross from pending → available
            $wallet->decrement('pending_balance_cents', $gross);
            $wallet->increment('available_balance_cents', $gross);
            $wallet->refresh();

            $releaseTransaction = WalletTransaction::create([
                'vendor_wallet_id'    => $wallet->id,
                'sub_order_id'        => $subOrder->id,
                'type'                => 'credit',
                'reason'              => 'escrow_release',
                'amount_cents'        => $gross,
                'amount_currency'     => $subOrder->subtotal_currency,
                'balance_after_cents' => $wallet->available_balance_cents,
                'reference'           => 'ESC-' . $subOrder->id,
            ]);

            // Deduct commission from available_balance as separate transaction
            if ($commission > 0) {
                $wallet->decrement('available_balance_cents', $commission);
                $wallet->refresh();

                WalletTransaction::create([
                    'vendor_wallet_id'    => $wallet->id,
                    'sub_order_id'        => $subOrder->id,
                    'type'                => 'debit',
                    'reason'              => 'commission_fee',
                    'amount_cents'        => -$commission,
                    'amount_currency'     => $subOrder->subtotal_currency,
                    'balance_after_cents' => $wallet->available_balance_cents,
                    'reference'           => 'COM-' . $subOrder->id,
                    'note'                => "Platform commission {$commissionPct}%",
                ]);
            }

            return $releaseTransaction;
        });
    }
}
