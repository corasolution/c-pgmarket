<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\SubOrder;
use App\Notifications\OrderAcceptedNotification;
use App\Notifications\OrderDeliveredNotification;
use App\Notifications\OrderPackedNotification;
use App\Notifications\OrderShippedNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

/**
 * Model observer on SubOrder — sends buyer email notifications
 * when order status transitions through the fulfillment lifecycle.
 *
 * Registered via SubOrder::updated() in AppServiceProvider::boot().
 */
final class SendOrderLifecycleNotifications
{
    /** @var array<string, class-string<Notification>> */
    private const STATUS_NOTIFICATION_MAP = [
        'accepted'   => OrderAcceptedNotification::class,
        'packed'     => OrderPackedNotification::class,
        'picked_up'  => OrderShippedNotification::class,
        'in_transit' => OrderShippedNotification::class,
        'delivered'  => OrderDeliveredNotification::class,
    ];

    public static function observe(): void
    {
        SubOrder::updated(function (Model $subOrder): void {
            /** @var SubOrder $subOrder */
            if (! $subOrder->wasChanged('status')) {
                return;
            }

            $status = $subOrder->status;
            $notificationClass = self::STATUS_NOTIFICATION_MAP[$status] ?? null;

            if ($notificationClass === null) {
                return;
            }

            $buyer = $subOrder->order?->buyer;

            if ($buyer === null) {
                return;
            }

            $buyer->notify(new $notificationClass($subOrder));
        });
    }
}
