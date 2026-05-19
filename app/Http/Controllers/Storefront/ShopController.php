<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

final class ShopController extends Controller
{
    public function show(string $slug): Response
    {
        $shop = Shop::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->with([
                'owner:id,name',
                'products' => fn ($q) => $q
                    ->where('status', 'active')
                    ->withCount('reviews')
                    ->with([
                        'variants'  => fn ($q) => $q->where('is_active', true)->orderBy('price_cents'),
                        'category:id,name_i18n,slug',
                    ])
                    ->orderByDesc('is_featured')
                    ->orderByDesc('created_at'),
            ])
            ->firstOrFail();

        $totalProducts = $shop->products->count();
        $totalReviews  = $shop->products->sum('reviews_count');

        return Inertia::render('storefront/shops/show', [
            'shop' => array_merge($shop->toArray(), [
                'logo'   => $shop->logo   ? $this->resolveUrl($shop->logo)   : null,
                'banner' => $shop->banner ? $this->resolveUrl($shop->banner) : null,
            ]),
            'stats' => [
                'total_products' => $totalProducts,
                'total_reviews' => $totalReviews,
                'member_since' => $shop->created_at?->format('M Y'),
            ],
        ]);
    }

    public function index(): Response
    {
        $shops = Shop::query()
            ->where('status', 'active')
            ->withCount('products')
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'slug', 'logo', 'banner', 'description_i18n', 'created_at']);

        return Inertia::render('storefront/shops/index', [
            'shops' => $shops->map(fn ($shop) => array_merge($shop->toArray(), [
                'logo'   => $shop->logo   ? $this->resolveUrl($shop->logo)   : null,
                'banner' => $shop->banner ? $this->resolveUrl($shop->banner) : null,
            ])),
        ]);
    }

    private function resolveUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return '/storage/'.$path;
    }
}
