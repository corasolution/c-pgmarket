<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->where('status', 'active')
            ->with(['variants' => fn ($q) => $q->where('is_active', true), 'shop:id,name,slug,logo'])
            ->when($request->category_id, fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->category, function ($q) use ($request) {
                $q->whereHas('category', fn ($cq) => $cq->where('slug', $request->category));
            })
            ->when($request->q ?? $request->search, fn ($q, $search) => $q->whereRaw("LOWER(name_i18n->>'en') LIKE LOWER(?)", ['%'.$search.'%']))
            ->when($request->sort, function ($q, $sort) {
                match ($sort) {
                    'price_asc' => $q->orderByRaw('(SELECT MIN(price_cents) FROM product_variants WHERE product_variants.product_id = products.id AND is_active = true) ASC'),
                    'price_desc' => $q->orderByRaw('(SELECT MIN(price_cents) FROM product_variants WHERE product_variants.product_id = products.id AND is_active = true) DESC'),
                    'featured' => $q->orderByDesc('is_featured')->orderByDesc('created_at'),
                    default => $q->orderByDesc('created_at'),
                };
            }, fn ($q) => $q->orderByDesc('created_at'))
            ->paginate($request->integer('per_page', 20));

        return response()->json($products);
    }

    public function show(string $slug): JsonResponse
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->with(['variants', 'shop', 'category'])
            ->firstOrFail();

        return response()->json($product);
    }
}
