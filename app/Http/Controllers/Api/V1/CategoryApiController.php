<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CategoryApiController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->withCount(['products' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $categories]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $category = Category::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->withCount(['products' => fn ($q) => $q->where('status', 'active')])
            ->firstOrFail();

        $ancestors = $category->ancestors();

        $categoryIds = $category->allDescendantIds();

        $query = Product::query()
            ->whereIn('category_id', $categoryIds)
            ->where('status', 'active')
            ->with([
                'variants' => fn ($q) => $q->where('is_active', true)->orderBy('price_cents')->limit(1),
                'shop:id,name,slug,logo',
            ]);

        // Price filtering (in cents)
        if ($request->filled('min_price')) {
            $minCents = (int) $request->input('min_price');
            $query->whereHas('variants', fn ($q) => $q->where('is_active', true)->where('price_cents', '>=', $minCents));
        }

        if ($request->filled('max_price')) {
            $maxCents = (int) $request->input('max_price');
            $query->whereHas('variants', fn ($q) => $q->where('is_active', true)->where('price_cents', '<=', $maxCents));
        }

        // Sorting
        $sort = $request->input('sort', 'newest');
        $query = match ($sort) {
            'price_asc' => $query->orderBy(
                Product::query()->selectRaw('MIN(price_cents)')
                    ->from('product_variants')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->where('product_variants.is_active', true)
            ),
            'price_desc' => $query->orderByDesc(
                Product::query()->selectRaw('MIN(price_cents)')
                    ->from('product_variants')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->where('product_variants.is_active', true)
            ),
            default => $query->orderByDesc('created_at'),
        };

        $products = $query->paginate(24)->withQueryString();

        return response()->json([
            'category' => $category,
            'ancestors' => $ancestors,
            'products' => $products,
        ]);
    }
}
