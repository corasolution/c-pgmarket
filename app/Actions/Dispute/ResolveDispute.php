<?php

declare(strict_types=1);

namespace App\Actions\Dispute;

use App\Actions\Payment\ReleaseEscrow;
use App\Contracts\PaymentGateway;
use App\Models\AuditLog;
use App\Models\Dispute;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Admin resolves a dispute with one of three outcomes:
 *   - 'favor_vendor'  → release escrow to vendor (if still held)
 *   - 'favor_buyer'   → refund buyer from pending balance
 *   - 'partial_refund' → refund a partial amount, release remainder to vendor
 */
final class ResolveDispute
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly ReleaseEscrow $releaseEscrow,
    ) {}

    public function __invoke(
        User $admin,
        Dispute $dispute,
        string $resolution,
        ?int $refundAmountCents = null,
    ): void {
        if ($admin->role !== 'admin') {
            throw new AuthorizationException('Only admins can resolve disputes.');
        }

        if (! in_array($dispute->status, ['open', 'under_review'], strict: true)) {
            throw new \DomainException("Cannot resolve a dispute with status '{$dispute->status}'.");
        }

        if (! in_array($resolution, ['favor_vendor', 'favor_buyer', 'partial_refund'], strict: true)) {
            throw new \DomainException("Invalid resolution: {$resolution}");
        }

        if ($resolution === 'partial_refund' && ($refundAmountCents === null || $refundAmountCents <= 0)) {
            throw new \DomainException('Partial refund requires a positive refund amount.');
        }

        DB::transaction(function () use ($admin, $dispute, $resolution, $refundAmountCents): void {
            $orderItem = $dispute->orderItem()->with('subOrder.shop.wallet', 'subOrder.order.payment')->firstOrFail();
            $subOrder = $orderItem->subOrder;

            $escrowReleased = WalletTransaction::where('sub_order_id', $subOrder->id)
                ->where('reason', 'escrow_release')
                ->exists();

            match ($resolution) {
                'favor_vendor' => $this->favorVendor($subOrder, $escrowReleased),
                'favor_buyer' => $this->favorBuyer($subOrder, $orderItem, $escrowReleased),
                'partial_refund' => $this->partialRefund($subOrder, $orderItem, $escrowReleased, $refundAmountCents),
            };

            $dispute->update([
                'status'      => 'resolved',
                'resolution'  => $resolution,
                'resolved_by' => $admin->id,
                'resolved_at' => now(),
            ]);

            // Mark suborder as disputed-resolved
            $subOrder->update(['status' => 'completed']);

            AuditLog::create([
                'user_id'        => $admin->id,
                'action'         => 'dispute.resolve',
                'auditable_type' => Dispute::class,
                'auditable_id'   => $dispute->id,
                'before'         => ['status' => 'open'],
                'after'          => [
                    'status'     => 'resolved',
                    'resolution' => $resolution,
                    'refund_cents' => $refundAmountCents,
                ],
            ]);
        });
    }

    private function favorVendor(\App\Models\SubOrder $subOrder, bool $escrowReleased): void
    {
        if (! $escrowReleased) {
            ($this->releaseEscrow)($subOrder);
        }
    }

    private function favorBuyer(
        \App\Models\SubOrder $subOrder,
        \App\Models\OrderItem $orderItem,
        bool $escrowReleased,
    ): void {
        $wallet = $subOrder->shop->wallet()->lockForUpdate()->first();
        if (! $wallet) {
            return;
        }

        $refundAmount = $orderItem->subtotal_cents;

        $balanceField = $escrowReleased ? 'available_balance_cents' : 'pending_balance_cents';
        $wallet->decrement($balanceField, $refundAmount);
        $wallet->refresh();

        WalletTransaction::create([
            'vendor_wallet_id'    => $wallet->id,
            'sub_order_id'        => $subOrder->id,
            'type'                => 'debit',
            'reason'              => 'dispute_refund',
            'amount_cents'        => -$refundAmount,
            'amount_currency'     => $subOrder->subtotal_currency,
            'balance_after_cents' => $wallet->$balanceField,
            'reference'           => 'DISP-' . $subOrder->id,
            'note'                => 'Dispute resolved in favor of buyer',
        ]);
    }

    private function partialRefund(
        \App\Models\SubOrder $subOrder,
        \App\Models\OrderItem $orderItem,
        bool $escrowReleased,
        ?int $refundAmountCents,
    ): void {
        if ($refundAmountCents === null) {
            return;
        }

        if ($refundAmountCents > $orderItem->subtotal_cents) {
            throw new \DomainException('Refund amount exceeds order item value.');
        }

        $wallet = $subOrder->shop->wallet()->lockForUpdate()->first();
        if (! $wallet) {
            return;
        }

        $balanceField = $escrowReleased ? 'available_balance_cents' : 'pending_balance_cents';
        $wallet->decrement($balanceField, $refundAmountCents);
        $wallet->refresh();

        WalletTransaction::create([
            'vendor_wallet_id'    => $wallet->id,
            'sub_order_id'        => $subOrder->id,
            'type'                => 'debit',
            'reason'              => 'dispute_refund',
            'amount_cents'        => -$refundAmountCents,
            'amount_currency'     => $subOrder->subtotal_currency,
            'balance_after_cents' => $wallet->$balanceField,
            'reference'           => 'DISP-' . $subOrder->id,
            'note'                => "Partial dispute refund ({$refundAmountCents} cents)",
        ]);

        // Release remainder to vendor if escrow not yet released
        if (! $escrowReleased) {
            ($this->releaseEscrow)($subOrder);
        }
    }
}
