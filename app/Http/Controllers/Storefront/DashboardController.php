<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\VendorWallet;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $recentOrders = $user->orders()->latest()->limit(10)->get();

        $wallet = null;
        $isVendor = in_array($user->role, ['vendor_owner', 'vendor_staff'], strict: true);

        if ($isVendor) {
            $shop = $user->ownedShop ?? $user->staffShop;
            if ($shop !== null) {
                $wallet = VendorWallet::where('shop_id', $shop->id)->first();
            }
        }

        return Inertia::render('storefront/dashboard', [
            'recentOrders' => $recentOrders,
            'wallet' => $wallet,
            'isVendor' => $isVendor,
        ]);
    }
}
