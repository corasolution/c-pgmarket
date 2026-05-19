<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Events\Order\OrderCreated;
use App\Models\Cart;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\SubOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateOrder
{
    /**
     * @param  array<string, mixed>  $shippingAddress
     */
    public function __invoke(Cart $cart, User $buyer, array $shippingAddress, ?string $note = null): Order
    {
        return DB::transaction(function () use ($cart, $buyer, $shippingAddress, $note): Order {
            $items = $cart->items()->with('variant.product')->get();

            // Validate stock for all items (with pessimistic lock)
            foreach ($items as $cartItem) {
                $variant = ProductVariant::lockForUpdate()->with('product')->find($cartItem->product_variant_id);

                if ($variant === null) {
                    throw new \DomainException("Product variant no longer exists.");
                }

                if ($variant->product->stock_track && $variant->stock_quantity < $cartItem->quantity) {
                    $name = $variant->product->name_i18n['en'] ?? $variant->sku;
                    throw new \DomainException(
                        "Insufficient stock for \"{$name}\". Available: {$variant->stock_quantity}, requested: {$cartItem->quantity}."
                    );
                }
            }

            // Deduct stock for tracked products only
            foreach ($items as $cartItem) {
                $tracksStock = $cartItem->variant->product->stock_track ?? false;
                if ($tracksStock) {
                    ProductVariant::where('id', $cartItem->product_variant_id)
                        ->decrement('stock_quantity', $cartItem->quantity);
                }
            }

            $totalCents = $items->sum(fn ($item) => $item->unit_price_cents * $item->quantity);

            $order = Order::create([
                'reference' => 'ORD-'.now()->format('Y').'-'.strtoupper(uniqid()),
                'buyer_id' => $buyer->id,
                'status' => 'pending',
                'total_cents' => $totalCents,
                'total_currency' => 'USD',
                'shipping_address' => $shippingAddress,
                'note' => $note,
            ]);

            // Group items by shop and create one SubOrder per shop
            $itemsByShop = $items->groupBy(fn ($item) => $item->variant->product->shop_id);

            foreach ($itemsByShop as $shopId => $shopItems) {
                $subtotal = $shopItems->sum(fn ($item) => $item->unit_price_cents * $item->quantity);

                $subOrder = SubOrder::create([
                    'order_id' => $order->id,
                    'shop_id' => $shopId,
                    'status' => 'pending',
                    'subtotal_cents' => $subtotal,
                    'subtotal_currency' => 'USD',
                    'shipping_fee_cents' => 0,
                ]);

                foreach ($shopItems as $cartItem) {
                    $subOrder->items()->create([
                        'product_variant_id' => $cartItem->product_variant_id,
                        'product_name_snapshot' => $cartItem->variant->product->name_i18n['en'] ?? '',
                        'variant_sku_snapshot' => $cartItem->variant->sku,
                        'image_snapshot' => $cartItem->variant->image,
                        'options_snapshot' => $cartItem->variant->options,
                        'quantity' => $cartItem->quantity,
                        'unit_price_cents' => $cartItem->unit_price_cents,
                        'unit_price_currency' => $cartItem->unit_price_currency,
                    ]);
                }
            }

            $cart->items()->delete();

            event(new OrderCreated($order));

            return $order;
        });
    }
}
