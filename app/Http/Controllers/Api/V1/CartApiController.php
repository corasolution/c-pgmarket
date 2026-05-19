<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CartApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);

        $cart->load([
            'items.variant.product' => fn ($q) => $q->select('id', 'shop_id', 'name_i18n', 'images', 'slug'),
            'items.variant.product.shop:id,name,slug,logo',
        ]);

        return response()->json($cart);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $variant = ProductVariant::where('id', $validated['variant_id'])
            ->where('is_active', true)
            ->firstOrFail();

        if ($variant->stock_quantity < $validated['quantity']) {
            return response()->json([
                'message' => 'Insufficient stock.',
                'available' => $variant->stock_quantity,
            ], 422);
        }

        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);

        $existingItem = CartItem::where('cart_id', $cart->id)
            ->where('product_variant_id', $variant->id)
            ->first();

        if ($existingItem !== null) {
            $newQuantity = $existingItem->quantity + $validated['quantity'];

            if ($variant->stock_quantity < $newQuantity) {
                return response()->json([
                    'message' => 'Insufficient stock for the combined quantity.',
                    'available' => $variant->stock_quantity,
                    'current_in_cart' => $existingItem->quantity,
                ], 422);
            }

            $existingItem->update(['quantity' => $newQuantity]);
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_variant_id' => $variant->id,
                'quantity' => $validated['quantity'],
                'unit_price_cents' => $variant->price_cents,
                'unit_price_currency' => $variant->price_currency,
            ]);
        }

        $cart->load([
            'items.variant.product' => fn ($q) => $q->select('id', 'shop_id', 'name_i18n', 'images', 'slug'),
            'items.variant.product.shop:id,name,slug,logo',
        ]);

        return response()->json($cart, 201);
    }

    public function update(Request $request, int $itemId): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $cart = Cart::where('user_id', $request->user()->id)->firstOrFail();

        $item = CartItem::where('id', $itemId)
            ->where('cart_id', $cart->id)
            ->firstOrFail();

        $variant = ProductVariant::findOrFail($item->product_variant_id);

        if ($variant->stock_quantity < $validated['quantity']) {
            return response()->json([
                'message' => 'Insufficient stock.',
                'available' => $variant->stock_quantity,
            ], 422);
        }

        $item->update(['quantity' => $validated['quantity']]);

        $cart->load([
            'items.variant.product' => fn ($q) => $q->select('id', 'shop_id', 'name_i18n', 'images', 'slug'),
            'items.variant.product.shop:id,name,slug,logo',
        ]);

        return response()->json($cart);
    }

    public function destroy(Request $request, int $itemId): JsonResponse
    {
        $cart = Cart::where('user_id', $request->user()->id)->firstOrFail();

        $item = CartItem::where('id', $itemId)
            ->where('cart_id', $cart->id)
            ->firstOrFail();

        $item->delete();

        $cart->load([
            'items.variant.product' => fn ($q) => $q->select('id', 'shop_id', 'name_i18n', 'images', 'slug'),
            'items.variant.product.shop:id,name,slug,logo',
        ]);

        return response()->json($cart);
    }
}
