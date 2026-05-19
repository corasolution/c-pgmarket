<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

final class ProductController extends Controller
{
    public function show(string $slug): Response
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->with(['variants' => fn ($q) => $q->where('is_active', true), 'shop', 'category', 'reviews.buyer'])
            ->firstOrFail();

        $related = Product::query()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('status', 'active')
            ->with(['variants' => fn ($q) => $q->where('is_active', true)->orderBy('price_cents'), 'shop'])
            ->inRandomOrder()
            ->limit(6)
            ->get();

        return Inertia::render('storefront/products/show', [
            'product'         => $product,
            'relatedProducts' => $related,
        ]);
    }
}
