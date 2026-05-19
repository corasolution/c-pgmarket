<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\HeroSlide;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;

final class MobileHomeController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $featuredProducts = Product::query()
            ->where('status', 'active')
            ->with([
                'variants' => fn ($q) => $q->where('is_active', true)->orderBy('price_cents')->limit(1),
                'shop:id,name,slug,logo',
                'category:id,name_i18n,slug',
            ])
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->limit(12)
            ->get();

        $newArrivals = Product::query()
            ->where('status', 'active')
            ->with([
                'variants' => fn ($q) => $q->where('is_active', true)->orderBy('price_cents')->limit(1),
                'shop:id,name,slug,logo',
            ])
            ->orderByDesc('created_at')
            ->limit(12)
            ->get();

        $categories = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->withCount(['children' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->get();

        $featuredShops = Shop::query()
            ->where('status', 'active')
            ->withCount(['products' => fn ($q) => $q->where('status', 'active')])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $heroSlides = HeroSlide::active();

        return response()->json([
            'featured_products' => $featuredProducts,
            'new_arrivals' => $newArrivals,
            'categories' => $categories,
            'featured_shops' => $featuredShops,
            'hero_slides' => $heroSlides,
        ]);
    }
}
