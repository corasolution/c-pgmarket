<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\HeroSlide;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

final class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name_i18n', 'slug', 'image']);

        $featuredProducts = Product::query()
            ->where('status', 'active')
            ->where('is_featured', true)
            ->with([
                'variants' => fn ($q) => $q->where('is_active', true)->orderBy('price_cents'),
                'shop:id,name,slug',
            ])
            ->limit(12)
            ->get();

        $featuredShops = Shop::query()
            ->where('status', 'active')
            ->withCount('products')
            ->orderByDesc('created_at')
            ->limit(12)
            ->get(['id', 'name', 'slug', 'logo', 'created_at']);

        $newArrivals = Product::query()
            ->where('status', 'active')
            ->with([
                'variants' => fn ($q) => $q->where('is_active', true)->orderBy('price_cents'),
                'shop:id,name,slug',
            ])
            ->orderByDesc('created_at')
            ->limit(12)
            ->get();

        return Inertia::render('storefront/home', [
            'categories'      => $categories,
            'featuredProducts' => $featuredProducts,
            'featuredShops'   => $featuredShops->map(fn ($shop) => array_merge($shop->toArray(), [
                'logo' => $shop->logo ? Storage::disk('public')->url($shop->logo) : null,
            ])),
            'newArrivals'  => $newArrivals,
            'heroSlides'   => HeroSlide::active()->values(),
        ]);
    }
}
