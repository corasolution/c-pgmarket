<?php

declare(strict_types=1);

use App\Actions\Order\CreateOrder;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\User;

beforeEach(function (): void {
    $this->buyer = User::factory()->create(['role' => 'buyer']);

    $vendor = User::factory()->create(['role' => 'vendor_owner']);
    $this->shop = Shop::factory()->create(['owner_id' => $vendor->id, 'status' => 'active']);
    $category = Category::factory()->create();

    $product = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'category_id' => $category->id,
        'status' => 'active',
    ]);

    $this->variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_cents' => 1000,
        'stock_quantity' => 10,
    ]);

    $this->cart = Cart::factory()->create(['user_id' => $this->buyer->id]);
    CartItem::factory()->create([
        'cart_id' => $this->cart->id,
        'product_variant_id' => $this->variant->id,
        'quantity' => 2,
        'unit_price_cents' => 1000,
        'unit_price_currency' => 'USD',
    ]);
});

test('creates order with correct total and sub-order', function (): void {
    $order = app(CreateOrder::class)($this->cart, $this->buyer, []);

    expect($order)->toBeInstanceOf(Order::class)
        ->and($order->total_cents)->toBe(2000)
        ->and($order->buyer_id)->toBe($this->buyer->id)
        ->and($order->status)->toBe('pending')
        ->and($order->subOrders)->toHaveCount(1)
        ->and($order->subOrders->first()->shop_id)->toBe($this->shop->id)
        ->and($order->subOrders->first()->items)->toHaveCount(1);
});

test('cart is cleared after order is created', function (): void {
    app(CreateOrder::class)($this->cart, $this->buyer, []);

    expect($this->cart->items()->count())->toBe(0);
});

test('single shop cart creates exactly one sub-order', function (): void {
    $order = app(CreateOrder::class)($this->cart, $this->buyer, []);

    expect($order->subOrders)->toHaveCount(1);
});
