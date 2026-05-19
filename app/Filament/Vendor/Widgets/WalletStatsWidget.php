<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Widgets;

use App\Models\VendorWallet;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class WalletStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $wallet = VendorWallet::where('shop_id', $user?->shop_id)->first();

        $available = $wallet ? '$'.number_format($wallet->available_balance_cents / 100, 2) : '$0.00';
        $pending = $wallet ? '$'.number_format($wallet->pending_balance_cents / 100, 2) : '$0.00';
        $lifetime = $wallet ? '$'.number_format($wallet->lifetime_earned_cents / 100, 2) : '$0.00';

        return [
            Stat::make('Available Balance', $available)
                ->description('Ready to withdraw')
                ->color('success')
                ->icon('heroicon-o-banknotes'),
            Stat::make('Pending Balance', $pending)
                ->description('In escrow (7-day hold)')
                ->color('warning')
                ->icon('heroicon-o-clock'),
            Stat::make('Lifetime Earned', $lifetime)
                ->description('Total earnings to date')
                ->color('info')
                ->icon('heroicon-o-chart-bar'),
        ];
    }
}
