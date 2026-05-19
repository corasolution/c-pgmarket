<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class ImportPgMarketProductsSeeder extends Seeder
{
    /**
     * Map old site category codes to new site category slugs.
     * Falls back to creating categories if needed.
     */
    private array $categoryMap = [];

    public function run(): void
    {
        $json = file_get_contents(base_path('storage/app/pgmarket_products.json'));
        /** @var list<array<string, mixed>> $products */
        $products = json_decode($json, true);

        $this->command->info('Importing ' . count($products) . ' products...');

        // Build old shop_id → new shop mapping
        $shopMap = $this->buildShopMap($products);

        foreach ($products as $p) {
            $oldShopId = (int) ($p['shop_id'] ?? 0);
            $newShop = $shopMap[$oldShopId] ?? null;

            if ($newShop === null) {
                $this->command->warn("Skip: {$p['name']} — no matching shop for old shop_id={$oldShopId}");
                continue;
            }

            // Resolve or create category
            $categoryId = $this->resolveCategory($p);

            $slug = Str::slug($p['name'] ?? 'product');
            // Ensure unique slug
            $baseSlug = $slug;
            $counter = 1;
            while (Product::withoutGlobalScopes()->where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . Str::random(4);
                $counter++;
                if ($counter > 10) {
                    break;
                }
            }

            $priceCents = (int) round(((float) ($p['price'] ?? 0)) * 100);
            $description = $p['short_description'] ?? $p['description'] ?? '';

            // Build images array from old site data
            $images = [];
            if (!empty($p['list_image'])) {
                $images[] = 'https://pgmarket.online/storage/products/' . $p['list_image'];
            }
            if (!empty($p['images']) && is_array($p['images'])) {
                foreach ($p['images'] as $img) {
                    if (is_array($img) && !empty($img['image'])) {
                        $url = 'https://pgmarket.online/storage/products/' . $img['image'];
                        if (!in_array($url, $images, true)) {
                            $images[] = $url;
                        }
                    }
                }
            }

            $product = Product::withoutGlobalScopes()->updateOrCreate(
                ['slug' => $slug],
                [
                    'shop_id' => $newShop->id,
                    'category_id' => $categoryId,
                    'brand_id' => null,
                    'name_i18n' => [
                        'en' => $p['name'] ?? 'Product',
                        'km' => $p['name_kh'] ?? '',
                    ],
                    'description_i18n' => [
                        'en' => $description,
                        'km' => '',
                    ],
                    'slug' => $slug,
                    'images' => $images,
                    'status' => 'active',
                    'is_featured' => false,
                ],
            );

            // Create default variant if none exists
            if ($product->variants()->count() === 0) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => strtoupper(Str::slug($p['code'] ?? $p['name'] ?? 'SKU', '-')) . '-' . $product->id,
                    'price_cents' => $priceCents > 0 ? $priceCents : 100,
                    'price_currency' => 'USD',
                    'stock_quantity' => rand(5, 100),
                    'is_active' => true,
                    'options' => [],
                ]);
            }

            $this->command->info("OK: {$p['name']} → {$newShop->name} (\${$priceCents}/100)");
        }

        $this->command->info('Product import complete.');
    }

    /**
     * @param  list<array<string, mixed>>  $products
     * @return array<int, Shop>
     */
    private function buildShopMap(array $products): array
    {
        // Get all old API shop data
        $oldShopIds = array_unique(array_column($products, 'shop_id'));

        // Load API shop names to match with new DB
        $apiShopsJson = file_get_contents(base_path('storage/app/pgmarket_shops.json'));
        $apiShops = json_decode($apiShopsJson, true);

        $map = [];
        foreach ($apiShops as $apiShop) {
            $newShop = Shop::where('name', $apiShop['name'])->first();
            if ($newShop) {
                $map[(int) $apiShop['id']] = $newShop;
            }
        }

        return $map;
    }

    private function resolveCategory(array $p): int
    {
        $catCode = $p['category']['code'] ?? $p['category_code'] ?? null;
        $catName = $p['category']['name'] ?? null;
        $catNameKh = $p['category']['name_kh'] ?? null;
        $parentCode = $p['category']['parent_code'] ?? null;

        if ($catCode && isset($this->categoryMap[$catCode])) {
            return $this->categoryMap[$catCode];
        }

        // Try to find existing category by slug
        $slug = Str::slug($catCode ?? $catName ?? 'general');
        $category = Category::where('slug', $slug)->first();

        if ($category) {
            if ($catCode) {
                $this->categoryMap[$catCode] = $category->id;
            }
            return $category->id;
        }

        // Resolve parent if exists
        $parentId = null;
        if ($parentCode) {
            $parentSlug = Str::slug($parentCode);
            $parent = Category::where('slug', $parentSlug)->first();
            if (!$parent) {
                $parent = Category::create([
                    'parent_id' => null,
                    'name_i18n' => ['en' => str_replace('-', ' ', ucwords($parentCode, '-'))],
                    'slug' => $parentSlug,
                    'is_active' => true,
                    'sort_order' => 0,
                ]);
            }
            $parentId = $parent->id;
        }

        // Create category
        $category = Category::create([
            'parent_id' => $parentId,
            'name_i18n' => [
                'en' => $catName ?? str_replace('-', ' ', ucwords($catCode ?? 'General', '-')),
                'km' => $catNameKh ?? '',
            ],
            'slug' => $slug,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        if ($catCode) {
            $this->categoryMap[$catCode] = $category->id;
        }

        return $category->id;
    }
}
