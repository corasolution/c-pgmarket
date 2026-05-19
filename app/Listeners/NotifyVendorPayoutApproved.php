<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Payout\PayoutApproved;
use App\Models\AuditLog;
use App\Models\Payout;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyVendorPayoutApproved implements ShouldQueue
{
    public function handle(PayoutApproved $event): void
    {
        $shop = $event->payout->shop;

        AuditLog::create([
            'user_id'        => $shop?->owner_id,
            'action'         => 'payout.approved',
            'auditable_type' => Payout::class,
            'auditable_id'   => $event->payout->id,
            'after'          => [
                'amount_cents' => $event->payout->amount_cents,
                'status'       => 'approved',
            ],
        ]);

        $shop?->owner?->notify(
            new \App\Notifications\PayoutApprovedNotification($event->payout)
        );
    }
}
