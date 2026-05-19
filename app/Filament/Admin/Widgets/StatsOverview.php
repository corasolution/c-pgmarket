<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Product;
use App\Models\Shop;
use App\Models\SubOrder;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalRevenue = Payment::where('status', 'paid')->sum('amount_cents');
        $monthRevenue = Payment::where('status', 'paid')
            ->where('paid_at', '>=', now()->startOfMonth())
            ->sum('amount_cents');

        $pendingOrders = SubOrder::where('status', 'pending')->count();
        $pendingPayouts = Payout::where('status', 'pending')->count();
        $pendingShops = Shop::where('status', 'submitted')->count();

        return [
            Stat::make('Total Revenue', '$' . number_format($totalRevenue / 100, 2))
                ->description('$' . number_format($monthRevenue / 100, 2) . ' this month')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('success')
                ->chart($this->getRevenueChart()),

            Stat::make('Total Orders', (string) Order::count())
                ->description($pendingOrders . ' pending action')
                ->descriptionIcon('heroicon-o-clock')
                ->color($pendingOrders > 0 ? 'warning' : 'success'),

            Stat::make('Active Shops', (string) Shop::where('status', 'active')->count())
                ->description($pendingShops . ' awaiting approval')
                ->descriptionIcon('heroicon-o-building-storefront')
                ->color($pendingShops > 0 ? 'warning' : 'info'),

            Stat::make('Total Users', (string) User::count())
                ->description(User::where('role', 'buyer')->count() . ' buyers')
                ->descriptionIcon('heroicon-o-users')
                ->color('info'),

            Stat::make('Active Products', (string) Product::where('status', 'active')->count())
                ->description(Product::where('status', 'draft')->count() . ' drafts')
                ->descriptionIcon('heroicon-o-shopping-bag')
                ->color('primary'),

            Stat::make('Pending Payouts', (string) $pendingPayouts)
                ->description('$' . number_format(Payout::where('status', 'pending')->sum('amount_cents') / 100, 2) . ' total')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color($pendingPayouts > 0 ? 'danger' : 'success'),
        ];
    }

    /**
     * @return array<int>
     */
    private function getRevenueChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $data[] = (int) Payment::where('status', 'paid')
                ->whereDate('paid_at', now()->subDays($i))
                ->sum('amount_cents');
        }

        return $data;
    }
}
