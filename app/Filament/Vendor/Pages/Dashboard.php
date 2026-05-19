<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Pages;

use App\Models\Product;
use App\Models\Shop;
use App\Models\SubOrder;
use App\Models\VendorWallet;
use Filament\Pages\Dashboard as BaseDashboard;

final class Dashboard extends BaseDashboard
{
    protected string $view = 'filament.vendor.pages.dashboard';

    public function getWidgets(): array
    {
        return []; // rendered in blade directly
    }

    public function getViewData(): array
    {
        $user   = auth()->user();
        $shop   = Shop::where('owner_id', $user?->id)->first();
        $wallet = $shop ? VendorWallet::where('shop_id', $shop->id)->first() : null;

        $totalProducts = $shop
            ? Product::where('shop_id', $shop->id)->where('status', 'active')->count()
            : 0;

        $totalOrders = $shop
            ? SubOrder::where('shop_id', $shop->id)->count()
            : 0;

        $pendingOrders = $shop
            ? SubOrder::where('shop_id', $shop->id)->where('status', 'pending')->count()
            : 0;

        $recentOrders = $shop
            ? SubOrder::where('shop_id', $shop->id)
                ->with(['order', 'items'])
                ->latest()
                ->limit(5)
                ->get()
            : collect();

        return [
            'user'          => $user,
            'shop'          => $shop,
            'wallet'        => $wallet,
            'totalProducts' => $totalProducts,
            'totalOrders'   => $totalOrders,
            'pendingOrders' => $pendingOrders,
            'recentOrders'  => $recentOrders,
            'available'     => $wallet ? number_format($wallet->available_balance_cents / 100, 2) : '0.00',
            'pending'       => $wallet ? number_format($wallet->pending_balance_cents / 100, 2) : '0.00',
            'lifetime'      => $wallet ? number_format($wallet->lifetime_earned_cents / 100, 2) : '0.00',
            'currency'      => $wallet?->available_balance_currency ?? 'USD',
        ];
    }
}
