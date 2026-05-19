<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CartController extends Controller
{
    public function index(Request $request): Response
    {
        $cart = $this->resolveCart($request);

        return Inertia::render('storefront/cart', [
            'cart' => $cart->load('items.variant.product'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $variant = ProductVariant::with('product')->findOrFail($validated['variant_id']);

        // Validate stock availability
        $cart = $this->resolveCart($request);
        $existing = $cart->items()->where('product_variant_id', $variant->id)->first();
        $currentQty = $existing?->quantity ?? 0;
        $newTotal = $currentQty + $validated['quantity'];

        if ($variant->product->stock_track && $newTotal > $variant->stock_quantity) {
            return back()->withErrors([
                'quantity' => "Only {$variant->stock_quantity} available in stock.",
            ]);
        }

        if ($existing !== null) {
            $existing->increment('quantity', $validated['quantity']);
        } else {
            $cart->items()->create([
                'product_variant_id' => $variant->id,
                'quantity' => $validated['quantity'],
                'unit_price_cents' => $variant->price_cents,
                'unit_price_currency' => $variant->price_currency,
            ]);
        }

        return back();
    }

    public function destroy(Request $request, int $itemId): RedirectResponse
    {
        $cart = $this->resolveCart($request);
        $cart->items()->where('id', $itemId)->delete();

        return back();
    }

    private function resolveCart(Request $request): Cart
    {
        if ($request->user() !== null) {
            return Cart::firstOrCreate(['user_id' => $request->user()->id]);
        }

        $sessionId = $request->session()->getId();

        return Cart::firstOrCreate(['session_id' => $sessionId]);
    }
}
