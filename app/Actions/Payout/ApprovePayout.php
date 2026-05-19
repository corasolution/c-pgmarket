<?php

declare(strict_types=1);

namespace App\Actions\Payout;

use App\Events\Payout\PayoutApproved;
use App\Models\AbaBeneficiary;
use App\Models\AuditLog;
use App\Models\Payout;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Payment\AbaPayoutService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ApprovePayout
{
    public function __construct(private readonly AbaPayoutService $payoutService) {}

    public function __invoke(User $admin, Payout $payout): void
    {
        if ($admin->role !== 'admin') {
            throw new AuthorizationException('Only admins can approve payouts.');
        }

        DB::transaction(function () use ($payout, $admin): void {
            $payout->refresh();

            if ($payout->status !== 'pending') {
                throw new \DomainException("Cannot approve a payout with status '{$payout->status}'.");
            }

            $before = ['status' => $payout->status];

            $wallet = $payout->shop->wallet()->lockForUpdate()->firstOrFail();

            if ($wallet->available_balance_cents < $payout->amount_cents) {
                throw new \DomainException('Insufficient available balance for this payout.');
            }

            // Try ABA payout first (before debiting wallet)
            $beneficiary = AbaBeneficiary::withoutGlobalScopes()
                ->where('shop_id', $payout->shop_id)
                ->where('status', 'active')
                ->first();

            $abaTransactionId = null;
            $abaExternalRef = null;

            if ($beneficiary !== null) {
                $tranId = 'PO-' . $payout->id . '-' . now()->format('Ymd');
                $amountDollars = (float) number_format($payout->amount_cents / 100, 2, '.', '');

                $response = $this->payoutService->payout(
                    tranId: $tranId,
                    beneficiaries: [['account' => $beneficiary->payee, 'amount' => $amountDollars]],
                    amountCents: $payout->amount_cents,
                    currency: $payout->amount_currency,
                );

                $responseCode = (string) ($response['status']['code'] ?? $response['status'] ?? '');

                if (! in_array($responseCode, ['0', '4'], strict: true)) {
                    $message = $response['status']['message'] ?? 'Unknown error';
                    Log::error('ABA payout failed', [
                        'payout_id' => $payout->id,
                        'tran_id'   => $tranId,
                        'code'      => $responseCode,
                        'message'   => $message,
                        'response'  => $response,
                    ]);

                    $payout->update(['status' => 'failed']);

                    throw new \RuntimeException("ABA payout failed: {$message} (code: {$responseCode})");
                }

                $abaTransactionId = $response['transaction_id'] ?? $tranId;
                $abaExternalRef = $response['external_reference'] ?? null;
            }

            // ABA succeeded (or no beneficiary) — now debit wallet
            $payout->update([
                'status'             => 'paid',
                'approved_by'        => $admin->id,
                'approved_at'        => now(),
                'aba_transaction_id' => $abaTransactionId,
                'aba_external_ref'   => $abaExternalRef,
            ]);

            $newBalance = $wallet->available_balance_cents - $payout->amount_cents;

            $wallet->decrement('available_balance_cents', $payout->amount_cents);

            WalletTransaction::create([
                'vendor_wallet_id'    => $wallet->id,
                'type'                => 'debit',
                'reason'              => 'payout',
                'amount_cents'        => -$payout->amount_cents,
                'amount_currency'     => $payout->amount_currency,
                'balance_after_cents' => $newBalance,
                'reference'           => 'PAYOUT-' . $payout->id,
            ]);

            AuditLog::create([
                'user_id'        => $admin->id,
                'action'         => 'payout.approve',
                'auditable_type' => Payout::class,
                'auditable_id'   => $payout->id,
                'before'         => $before,
                'after'          => [
                    'status'             => $payout->status,
                    'aba_transaction_id' => $payout->aba_transaction_id,
                ],
            ]);

            event(new PayoutApproved($payout));
        });
    }
}
