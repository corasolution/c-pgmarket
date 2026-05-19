<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FavoriteApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $favorites = Favorite::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'product' => fn ($q) => $q->where('status', 'active'),
                'product.variants' => fn ($q) => $q->where('is_active', true)->orderBy('price_cents')->limit(1),
                'product.shop:id,name,slug,logo',
            ])
            ->latest('created_at')
            ->paginate(20);

        // Filter out favorites whose product was deleted or deactivated
        $favorites->setCollection(
            $favorites->getCollection()->filter(fn (Favorite $fav) => $fav->product !== null)
        );

        return response()->json($favorites);
    }

    public function toggle(Request $request, Product $product): JsonResponse
    {
        $userId = $request->user()->id;

        $existing = Favorite::where('user_id', $userId)
            ->where('product_id', $product->id)
            ->first();

        if ($existing !== null) {
            $existing->delete();

            return response()->json(['favorited' => false]);
        }

        Favorite::create([
            'user_id' => $userId,
            'product_id' => $product->id,
        ]);

        return response()->json(['favorited' => true], 201);
    }
}
