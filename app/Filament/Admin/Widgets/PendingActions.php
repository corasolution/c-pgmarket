<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Dispute;
use App\Models\Payout;
use App\Models\Shop;
use App\Models\SubOrder;
use Filament\Widgets\Widget;

final class PendingActions extends Widget
{
    protected static ?int $sort = 3;

    protected string $view = 'filament.admin.widgets.pending-actions';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'items' => array_filter([
                [
                    'label' => 'Orders needing action',
                    'count' => SubOrder::where('status', 'pending')->count(),
                    'color' => '#f59e0b',
                    'bg' => '#fffbeb',
                    'border' => '#fef3c7',
                    'url' => route('filament.admin.resources.orders.index'),
                ],
                [
                    'label' => 'Shops awaiting approval',
                    'count' => Shop::where('status', 'submitted')->count(),
                    'color' => '#6366f1',
                    'bg' => '#eef2ff',
                    'border' => '#e0e7ff',
                    'url' => route('filament.admin.resources.shops.index'),
                ],
                [
                    'label' => 'Payouts to review',
                    'count' => Payout::where('status', 'pending')->count(),
                    'color' => '#ef4444',
                    'bg' => '#fef2f2',
                    'border' => '#fee2e2',
                    'url' => route('filament.admin.resources.payouts.index'),
                ],
                [
                    'label' => 'Open disputes',
                    'count' => Dispute::where('status', 'open')->count(),
                    'color' => '#f97316',
                    'bg' => '#fff7ed',
                    'border' => '#ffedd5',
                    'url' => route('filament.admin.resources.disputes.index'),
                ],
            ], fn ($item) => $item['count'] > 0),
        ];
    }
}
