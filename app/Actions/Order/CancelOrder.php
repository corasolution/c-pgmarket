<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Support\Enums\UserRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class CancelOrder
{
    private const BUYER_CANCELLABLE = ['pending', 'paid'];

    private const ADMIN_CANCELLABLE = ['pending', 'paid', 'accepted', 'packed'];

    public function __invoke(Order $order, User $actor, string $reason = ''): void
    {
        $this->authorize($order, $actor);

        $wasPaid = $order->status !== 'pending';

        DB::transaction(function () use ($order, $actor, $reason, $wasPaid): void {
            $order->update([
                'status'              => 'cancelled',
                'cancellation_reason' => $reason ?: null,
            ]);

            foreach ($order->subOrders as $subOrder) {
                if (in_array($subOrder->status, ['delivered', 'completed', 'cancelled'], strict: true)) {
                    continue;
                }

                $subOrder->update(['status' => 'cancelled']);

                // Restore stock for tracked products
                foreach ($subOrder->items()->with('variant.product')->get() as $orderItem) {
                    if ($orderItem->variant?->product?->stock_track) {
                        ProductVariant::where('id', $orderItem->product_variant_id)
                            ->increment('stock_quantity', $orderItem->quantity);
                    }
                }

                // Reverse pending_balance if payment was already credited
                if (! $wasPaid) {
                    continue;
                }

                $wallet = $subOrder->shop->wallet()->lockForUpdate()->first();
                if (! $wallet) {
                    continue;
                }

                // Check if funds were credited for this suborder
                $credited = WalletTransaction::where('sub_order_id', $subOrder->id)
                    ->where('reason', 'order_payment')
                    ->exists();

                if (! $credited) {
                    continue;
                }

                // Check if escrow was already released
                $escrowReleased = WalletTransaction::where('sub_order_id', $subOrder->id)
                    ->where('reason', 'escrow_release')
                    ->exists();

                $balanceField = $escrowReleased ? 'available_balance_cents' : 'pending_balance_cents';
                $wallet->decrement($balanceField, $subOrder->subtotal_cents);
                $wallet->refresh();

                WalletTransaction::create([
                    'vendor_wallet_id' => $wallet->id,
                    'sub_order_id'     => $subOrder->id,
                    'type'             => 'debit',
                    'reason'           => 'order_cancellation',
                    'amount_cents'     => -$subOrder->subtotal_cents,
                    'amount_currency'  => $subOrder->subtotal_currency,
                    'balance_after_cents' => $wallet->$balanceField,
                    'reference'        => 'CANCEL-' . $order->id,
                    'note'             => 'Order cancelled' . ($reason ? ": {$reason}" : ''),
                ]);
            }

            AuditLog::create([
                'user_id'        => $actor->id,
                'action'         => 'order.cancel',
                'auditable_type' => Order::class,
                'auditable_id'   => $order->id,
                'before'         => ['status' => $order->getOriginal('status')],
                'after'          => ['status' => 'cancelled', 'reason' => $reason],
            ]);
        });
    }

    private function authorize(Order $order, User $actor): void
    {
        if ($actor->role === UserRole::Admin->value) {
            if (! in_array($order->status, self::ADMIN_CANCELLABLE, strict: true)) {
                throw new \RuntimeException("Admin cannot cancel an order in '{$order->status}' status.");
            }

            return;
        }

        if ($actor->id === $order->buyer_id) {
            if (! in_array($order->status, self::BUYER_CANCELLABLE, strict: true)) {
                throw new \RuntimeException("Order cannot be cancelled in '{$order->status}' status.");
            }

            return;
        }

        throw new AuthorizationException('You are not authorised to cancel this order.');
    }
}
