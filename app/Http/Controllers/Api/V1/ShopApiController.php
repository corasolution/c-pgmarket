<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ShopApiController extends Controller
{
    public function index(): JsonResponse
    {
        $shops = Shop::query()
            ->where('status', 'active')
            ->withCount(['products' => fn ($q) => $q->where('status', 'active')])
            ->latest()
            ->paginate(20);

        $shops->getCollection()->transform(function (Shop $shop): Shop {
            $shop->logo = $this->resolveImageUrl($shop->logo);
            $shop->banner = $this->resolveImageUrl($shop->banner);

            return $shop;
        });

        return response()->json($shops);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $shop = Shop::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->withCount(['products' => fn ($q) => $q->where('status', 'active')])
            ->firstOrFail();

        $shop->logo = $this->resolveImageUrl($shop->logo);
        $shop->banner = $this->resolveImageUrl($shop->banner);

        $products = Product::query()
            ->where('shop_id', $shop->id)
            ->where('status', 'active')
            ->with([
                'variants' => fn ($q) => $q->where('is_active', true)->orderBy('price_cents')->limit(1),
            ])
            ->latest()
            ->paginate(24)
            ->withQueryString();

        $totalReviews = \App\Models\Review::query()
            ->whereHas('product', fn ($q) => $q->where('shop_id', $shop->id))
            ->count();

        return response()->json([
            'shop' => $shop,
            'products' => $products,
            'stats' => [
                'total_products' => $shop->products_count,
                'total_reviews' => $totalReviews,
                'member_since' => $shop->created_at?->toDateString(),
            ],
        ]);
    }

    private function resolveImageUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return '/storage/' . $path;
    }
}
