<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Models\SubOrder;
use App\Models\VendorWallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Credit a vendor's pending_balance after payment is confirmed.
 * Called once per SubOrder when PaymentReceived fires.
 * Uses double-entry: every credit has a matching WalletTransaction row.
 */
final class CreditWallet
{
    public function __invoke(SubOrder $subOrder, string $reason = 'order_payment'): WalletTransaction
    {
        return DB::transaction(function () use ($subOrder, $reason): WalletTransaction {
            $wallet = VendorWallet::firstOrCreate(
                ['shop_id' => $subOrder->shop_id],
                ['pending_balance_cents' => 0, 'pending_balance_currency' => 'USD',
                    'available_balance_cents' => 0, 'available_balance_currency' => 'USD',
                    'lifetime_earned_cents' => 0],
            );

            $wallet->increment('pending_balance_cents', $subOrder->subtotal_cents);
            $wallet->increment('lifetime_earned_cents', $subOrder->subtotal_cents);
            $wallet->refresh();

            return WalletTransaction::create([
                'vendor_wallet_id' => $wallet->id,
                'sub_order_id' => $subOrder->id,
                'type' => 'credit',
                'reason' => $reason,
                'amount_cents' => $subOrder->subtotal_cents,
                'amount_currency' => $subOrder->subtotal_currency,
                'balance_after_cents' => $wallet->pending_balance_cents,
                'reference' => 'PAY-'.$subOrder->id,
            ]);
        });
    }
}
