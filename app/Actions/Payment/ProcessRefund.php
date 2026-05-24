<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Contracts\PaymentGateway;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Notifications\RefundProcessedNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class ProcessRefund
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    public function __invoke(User $actor, Payment $payment, int $amountCents): void
    {
        if ($actor->role !== 'admin') {
            throw new AuthorizationException('Only admins can process refunds.');
        }

        if ($payment->status !== 'paid') {
            throw new \DomainException("Cannot refund a payment with status '{$payment->status}'.");
        }

        if ($amountCents > $payment->amount_cents) {
            throw new \DomainException('Refund amount exceeds original payment amount.');
        }

        DB::transaction(function () use ($actor, $payment, $amountCents): void {
            $success = $this->gateway->refund($payment, $amountCents);

            if (! $success) {
                throw new \RuntimeException('Payment gateway rejected the refund.');
            }

            $isFullRefund = $amountCents === $payment->amount_cents;
            $payment->update(['status' => $isFullRefund ? 'refunded' : 'partially_refunded']);
            $payment->order->update(['status' => $isFullRefund ? 'refunded' : 'refund_requested']);

            foreach ($payment->order->subOrders as $subOrder) {
                $wallet = $subOrder->shop->wallet()->lockForUpdate()->first();
                if (! $wallet) {
                    continue;
                }

                $subOrderShare = (int) round($amountCents * $subOrder->subtotal_cents / $payment->amount_cents);

                // Determine which balance to debit based on whether escrow was released
                $escrowReleased = WalletTransaction::where('sub_order_id', $subOrder->id)
                    ->where('reason', 'escrow_release')
                    ->exists();

                if ($escrowReleased) {
                    $wallet->decrement('available_balance_cents', $subOrderShare);
                } else {
                    $wallet->decrement('pending_balance_cents', $subOrderShare);
                }

                $wallet->refresh();

                WalletTransaction::create([
                    'vendor_wallet_id' => $wallet->id,
                    'sub_order_id' => $subOrder->id,
                    'type' => 'debit',
                    'reason' => 'refund',
                    'amount_cents' => -$subOrderShare,
                    'amount_currency' => $payment->amount_currency,
                    'balance_after_cents' => $escrowReleased
                        ? $wallet->available_balance_cents
                        : $wallet->pending_balance_cents,
                    'reference' => 'REF-'.$payment->id,
                    'note' => $escrowReleased ? 'Refund from available balance' : 'Refund from pending balance (escrow)',
                ]);

                if ($isFullRefund) {
                    $subOrder->update(['status' => 'refunded']);
                }
            }

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'payment.refund',
                'auditable_type' => Payment::class,
                'auditable_id' => $payment->id,
                'before' => ['status' => 'paid', 'amount_cents' => $payment->amount_cents],
                'after' => ['status' => $payment->status, 'refund_cents' => $amountCents],
            ]);

            // Notify buyer about the refund
            $buyer = $payment->order?->buyer;
            if ($buyer?->email) {
                $buyer->notify(new RefundProcessedNotification($payment, $amountCents));
            }
        });
    }
}
