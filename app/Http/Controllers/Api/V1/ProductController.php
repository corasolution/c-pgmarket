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
            ->with(['variants' => fn ($q) => $q->where('is_active', true)])
            ->when($request->category_id, fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->search, fn ($q) => $q->whereRaw("LOWER(name_i18n->>'en') LIKE LOWER(?)", ['%'.$request->search.'%']))
            ->paginate(20);

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
