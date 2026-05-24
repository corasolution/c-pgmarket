<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Widgets;

use App\Models\Shop;
use App\Services\SellerPerformanceService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class SellerPerformanceWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $user = auth()->user();
        $shop = Shop::where('owner_id', $user?->id)->first();

        if ($shop === null) {
            return [];
        }

        $metrics = app(SellerPerformanceService::class)->calculate($shop);

        return [
            Stat::make('Performance Score', $metrics['overall_score'] . '/100')
                ->description($metrics['overall_score'] >= 80 ? 'Excellent' : ($metrics['overall_score'] >= 60 ? 'Good' : 'Needs Improvement'))
                ->color($metrics['overall_score'] >= 80 ? 'success' : ($metrics['overall_score'] >= 60 ? 'warning' : 'danger')),

            Stat::make('Avg Rating', $metrics['avg_rating'] > 0 ? $metrics['avg_rating'] . ' / 5' : 'No reviews')
                ->description($metrics['total_orders'] . ' total orders')
                ->color($metrics['avg_rating'] >= 4 ? 'success' : ($metrics['avg_rating'] >= 3 ? 'warning' : 'gray')),

            Stat::make('Cancel Rate', $metrics['cancel_rate'] . '%')
                ->description('Dispute rate: ' . $metrics['dispute_rate'] . '%')
                ->color($metrics['cancel_rate'] <= 5 ? 'success' : ($metrics['cancel_rate'] <= 15 ? 'warning' : 'danger')),
        ];
    }
}
