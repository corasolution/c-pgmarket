<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\DeliveryProvider;
use App\Contracts\PaymentGateway;
use App\Events\Order\OrderCreated;
use App\Events\Payment\PaymentReceived;
use App\Events\Payout\PayoutApproved;
use App\Events\Shop\ShopApproved;
use App\Events\Shop\ShopSuspended;
use App\Listeners\CreditVendorWalletsOnPayment;
use App\Listeners\NotifyShopsOrderCreated;
use App\Listeners\NotifyVendorPayoutApproved;
use App\Listeners\NotifyVendorShopApproved;
use App\Listeners\NotifyVendorShopSuspended;
use App\Listeners\CreateDeliveryOnPayment;
use App\Listeners\ScheduleEscrowReleaseOnDelivery;
use App\Listeners\SendOrderLifecycleNotifications;
use App\Models\Product;
use App\Observers\ProductObserver;
use App\Services\Delivery\ApolloDeliveryProvider;
use App\Services\Payment\AbaPayWayGateway;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, AbaPayWayGateway::class);
        $this->app->bind(DeliveryProvider::class, ApolloDeliveryProvider::class);
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Event::listen(PaymentReceived::class, CreditVendorWalletsOnPayment::class);
        Event::listen(PaymentReceived::class, CreateDeliveryOnPayment::class);
        Event::listen(OrderCreated::class, NotifyShopsOrderCreated::class);
        Event::listen(OrderCreated::class, \App\Listeners\SendBuyerOrderConfirmation::class);
        Event::listen(ShopApproved::class, NotifyVendorShopApproved::class);
        Event::listen(ShopSuspended::class, NotifyVendorShopSuspended::class);
        Event::listen(PayoutApproved::class, NotifyVendorPayoutApproved::class);

        ScheduleEscrowReleaseOnDelivery::observe();
        SendOrderLifecycleNotifications::observe();

        Product::observe(ProductObserver::class);
    }
}
