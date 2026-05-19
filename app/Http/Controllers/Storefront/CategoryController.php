<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

final class CategoryController extends Controller
{
    public function index(): Response
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with([
                'children' => fn ($q) => $q
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->with([
                        'children' => fn ($q2) => $q2
                            ->where('is_active', true)
                            ->orderBy('sort_order'),
                    ]),
            ])
            ->orderBy('sort_order')
            ->get(['id', 'name_i18n', 'slug', 'image', 'parent_id', 'sort_order']);

        $categories->each(function (Category $cat): void {
            $cat->products_count = Product::whereIn('category_id', $cat->allDescendantIds())
                ->where('status', 'active')
                ->count();
        });

        return Inertia::render('storefront/categories/index', [
            'categories' => $categories,
        ]);
    }

    public function show(string $slug, \Illuminate\Http\Request $request): Response
    {
        $category = Category::where('slug', $slug)
            ->where('is_active', true)
            ->with([
                'children' => fn ($q) => $q
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->with([
                        'children' => fn ($q2) => $q2
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->select(['id', 'name_i18n', 'slug', 'parent_id', 'sort_order']),
                    ]),
            ])
            ->firstOrFail();

        $category->children->each(function (Category $child): void {
            $child->products_count = Product::whereIn('category_id', $child->allDescendantIds())
                ->where('status', 'active')
                ->count();
        });

        $ancestors = $category->ancestors();
        $allIds    = $category->allDescendantIds();

        $sort      = (string) $request->get('sort', 'newest');
        $perPage   = (int) $request->get('per_page', 25);
        $perPage   = in_array($perPage, [25, 50, 100], true) ? $perPage : 25;
        $brandSlug = (string) $request->get('brand', '');

        // Brands that have active products in this category subtree
        $brands = Brand::query()
            ->where('is_active', true)
            ->whereIn('id', Product::whereIn('category_id', $allIds)
                ->where('status', 'active')
                ->whereNotNull('brand_id')
                ->distinct()
                ->pluck('brand_id'))
            ->orderBy('sort_order')
            ->get(['id', 'name_i18n', 'slug', 'logo']);

        $selectedBrand = $brandSlug !== ''
            ? $brands->firstWhere('slug', $brandSlug)
            : null;

        $query = Product::whereIn('category_id', $allIds)
            ->where('status', 'active')
            ->with(['variants' => fn ($q) => $q->where('is_active', true)->orderBy('price_cents')]);

        if ($selectedBrand !== null) {
            $query->where('brand_id', $selectedBrand->id);
        }

        match ($sort) {
            'price_asc'  => $query->orderByRaw(
                "(SELECT MIN(price_cents) FROM product_variants WHERE product_id = products.id AND is_active = true) ASC NULLS LAST"
            ),
            'price_desc' => $query->orderByRaw(
                "(SELECT MIN(price_cents) FROM product_variants WHERE product_id = products.id AND is_active = true) DESC NULLS LAST"
            ),
            'featured'   => $query->orderByDesc('is_featured')->orderByDesc('created_at'),
            default      => $query->latest(),
        };

        $products = $query->paginate($perPage)->withQueryString();

        return Inertia::render('storefront/categories/show', [
            'category'      => $category,
            'ancestors'     => $ancestors,
            'products'      => $products,
            'brands'        => $brands,
            'selectedBrand' => $selectedBrand,
            'filters'       => [
                'sort'     => $sort,
                'per_page' => $perPage,
                'brand'    => $brandSlug,
            ],
        ]);
    }
}
