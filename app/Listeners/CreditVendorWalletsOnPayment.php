<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Payment\CreditWallet;
use App\Events\Payment\PaymentReceived;
use Illuminate\Contracts\Queue\ShouldQueue;

final class CreditVendorWalletsOnPayment implements ShouldQueue
{
    public function __construct(private readonly CreditWallet $creditWallet) {}

    public function handle(PaymentReceived $event): void
    {
        $order = $event->payment->order()->with('subOrders')->first();

        if ($order === null) {
            return;
        }

        // Update order status to paid
        $order->update(['status' => 'paid']);

        // Credit each shop's pending_balance
        foreach ($order->subOrders as $subOrder) {
            $subOrder->update(['status' => 'paid']);
            ($this->creditWallet)($subOrder, reason: 'order_payment');
        }
    }
}
