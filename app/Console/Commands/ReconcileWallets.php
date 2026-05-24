<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\VendorWallet;
use App\Models\WalletTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Compares each vendor wallet's stored balance against the sum of its
 * transactions. Logs a warning if any mismatch is found.
 *
 * Designed to run daily via the scheduler.
 */
final class ReconcileWallets extends Command
{
    protected $signature = 'wallets:reconcile';

    protected $description = 'Check vendor wallet balances against transaction ledger and report mismatches';

    public function handle(): int
    {
        $wallets = VendorWallet::with('shop')->get();
        $mismatches = 0;

        foreach ($wallets as $wallet) {
            // Sum all credit transactions (positive amounts)
            $credits = WalletTransaction::where('vendor_wallet_id', $wallet->id)
                ->where('amount_cents', '>', 0)
                ->sum('amount_cents');

            // Sum all debit transactions (negative amounts)
            $debits = WalletTransaction::where('vendor_wallet_id', $wallet->id)
                ->where('amount_cents', '<', 0)
                ->sum('amount_cents');

            $expectedTotal = (int) ($credits + $debits);
            $actualTotal = $wallet->pending_balance_cents + $wallet->available_balance_cents;

            if ($expectedTotal !== $actualTotal) {
                $shopName = $wallet->shop?->name ?? "Shop #{$wallet->shop_id}";
                $diff = $actualTotal - $expectedTotal;

                $this->error("MISMATCH: {$shopName} — expected {$expectedTotal}, actual {$actualTotal} (diff: {$diff})");

                Log::error('Wallet reconciliation mismatch', [
                    'shop_id'         => $wallet->shop_id,
                    'shop_name'       => $shopName,
                    'expected_cents'  => $expectedTotal,
                    'actual_cents'    => $actualTotal,
                    'difference_cents' => $diff,
                    'pending'         => $wallet->pending_balance_cents,
                    'available'       => $wallet->available_balance_cents,
                    'total_credits'   => $credits,
                    'total_debits'    => $debits,
                ]);

                $mismatches++;
            }
        }

        if ($mismatches === 0) {
            $this->info("All {$wallets->count()} wallet(s) reconciled successfully.");
        } else {
            $this->warn("{$mismatches} mismatch(es) found out of {$wallets->count()} wallets. Check logs.");
        }

        return $mismatches > 0 ? self::FAILURE : self::SUCCESS;
    }
}
