<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cancel unpaid orders older than 30 minutes, restore stock
Schedule::command('orders:expire-unpaid')->everyFiveMinutes();

// Notify vendors about low stock products (daily at 8am)
Schedule::command('stock:check-low')->dailyAt('08:00');

// Reconcile wallet balances against transaction ledger (daily at 2am)
Schedule::command('wallets:reconcile')->dailyAt('02:00');

// Notify users when wishlisted out-of-stock items return (every 30 min)
Schedule::command('stock:notify-back-in-stock')->everyThirtyMinutes();

// Activate/complete flash sales (every minute)
Schedule::command('flash-sales:manage')->everyMinute();
