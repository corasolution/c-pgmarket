<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ProductListController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $q        = (string) $request->get('q', '');
        $category = (string) $request->get('category', '');
        $minPrice = $request->get('min_price');
        $maxPrice = $request->get('max_price');
        $sort     = (string) $request->get('sort', 'newest');

        $query = Product::query()
            ->where('products.status', 'active')
            ->with([
                'variants'  => fn ($v) => $v->where('is_active', true)->orderBy('price_cents'),
                'shop:id,name,slug',
                'category:id,name_i18n,slug',
            ]);

        if ($q !== '') {
            $query->whereRaw("LOWER(name_i18n->>'en') LIKE LOWER(?)", ['%'.$q.'%']);
        }

        if ($category !== '') {
            $query->whereHas('category', fn ($c) => $c->where('slug', $category));
        }

        if ($minPrice !== null && is_numeric($minPrice)) {
            $minCents = (int) round((float) $minPrice * 100);
            $query->whereHas('variants', fn ($v) => $v->where('price_cents', '>=', $minCents)->where('is_active', true));
        }

        if ($maxPrice !== null && is_numeric($maxPrice)) {
            $maxCents = (int) round((float) $maxPrice * 100);
            $query->whereHas('variants', fn ($v) => $v->where('price_cents', '<=', $maxCents)->where('is_active', true));
        }

        match ($sort) {
            'price_asc'  => $query->orderByRaw(
                "(SELECT MIN(price_cents) FROM product_variants WHERE product_id = products.id AND is_active = true) ASC NULLS LAST"
            ),
            'price_desc' => $query->orderByRaw(
                "(SELECT MIN(price_cents) FROM product_variants WHERE product_id = products.id AND is_active = true) DESC NULLS LAST"
            ),
            'featured'   => $query->orderByDesc('is_featured')->orderByDesc('products.created_at'),
            default      => $query->orderByDesc('products.created_at'),
        };

        $products = $query->paginate(24)->withQueryString();

        $categories = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get(['id', 'name_i18n', 'slug']);

        return Inertia::render('storefront/products/index', [
            'products'   => $products,
            'categories' => $categories,
            'filters'    => [
                'q'         => $q,
                'category'  => $category,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'sort'      => $sort,
            ],
        ]);
    }
}
