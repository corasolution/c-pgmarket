<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;

test('vendor only sees their own shop products', function (): void {
    $vendorA = User::factory()->create(['role' => 'vendor_owner']);
    $vendorB = User::factory()->create(['role' => 'vendor_owner']);

    $shopA = Shop::factory()->create(['owner_id' => $vendorA->id]);
    $shopB = Shop::factory()->create(['owner_id' => $vendorB->id]);
    $vendorA->update(['shop_id' => $shopA->id]);
    $vendorB->update(['shop_id' => $shopB->id]);
    $category = Category::factory()->create();

    Product::factory()->create(['shop_id' => $shopA->id, 'category_id' => $category->id]);
    Product::factory()->create(['shop_id' => $shopB->id, 'category_id' => $category->id]);

    // As vendor A — scope must only return shopA's product
    $this->actingAs($vendorA);
    $products = Product::all();

    expect($products)->toHaveCount(1)
        ->and($products->first()->shop_id)->toBe($shopA->id);
});

test('vendor cannot see another vendors products via direct query', function (): void {
    $vendorA = User::factory()->create(['role' => 'vendor_owner']);
    $vendorB = User::factory()->create(['role' => 'vendor_owner']);

    $shopA = Shop::factory()->create(['owner_id' => $vendorA->id]);
    $shopB = Shop::factory()->create(['owner_id' => $vendorB->id]);
    $vendorA->update(['shop_id' => $shopA->id]);
    $category = Category::factory()->create();

    Product::factory()->create(['shop_id' => $shopB->id, 'category_id' => $category->id]);

    // Vendor A should see 0 products (only shopB has a product)
    $this->actingAs($vendorA);
    expect(Product::count())->toBe(0);
});

test('admin can see all products', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $vendor = User::factory()->create(['role' => 'vendor_owner']);
    $shop = Shop::factory()->create(['owner_id' => $vendor->id]);
    $category = Category::factory()->create();

    Product::factory(3)->create(['shop_id' => $shop->id, 'category_id' => $category->id]);

    $this->actingAs($admin);
    expect(Product::count())->toBe(3);
});
