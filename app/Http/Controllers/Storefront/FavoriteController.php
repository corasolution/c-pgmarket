<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

final class FavoriteController extends Controller
{
    public function index(Request $request): Response
    {
        $products = Product::withoutGlobalScopes()
            ->whereHas('favorites', fn ($q) => $q->where('user_id', $request->user()->id))
            ->where('status', 'active')
            ->with([
                'variants' => fn ($q) => $q->where('is_active', true)->orderBy('price_cents'),
                'shop:id,name,slug',
                'category:id,name_i18n,slug',
            ])
            ->latest()
            ->get();

        return Inertia::render('storefront/favorites', [
            'products' => $products,
        ]);
    }

    public function toggle(Request $request, Product $product): RedirectResponse
    {
        $userId = $request->user()->id;

        $existing = Favorite::where('user_id', $userId)
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            Favorite::create(['user_id' => $userId, 'product_id' => $product->id]);
        }

        Cache::forget('user_favorites_' . $userId);

        return back();
    }
}
