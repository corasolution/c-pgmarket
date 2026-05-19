<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

final class BrandsSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            ['en' => 'Apple',    'km' => 'Apple',    'slug' => 'apple'],
            ['en' => 'Samsung',  'km' => 'Samsung',  'slug' => 'samsung'],
            ['en' => 'Xiaomi',   'km' => 'Xiaomi',   'slug' => 'xiaomi'],
            ['en' => 'LG',       'km' => 'LG',       'slug' => 'lg'],
            ['en' => 'Sony',     'km' => 'Sony',     'slug' => 'sony'],
            ['en' => 'Anker',    'km' => 'Anker',    'slug' => 'anker'],
            ['en' => 'JBL',      'km' => 'JBL',      'slug' => 'jbl'],
            ['en' => 'Logitech', 'km' => 'Logitech', 'slug' => 'logitech'],
        ];

        $created = [];
        foreach ($brands as $i => $b) {
            $created[$b['slug']] = Brand::updateOrCreate(
                ['slug' => $b['slug']],
                [
                    'name_i18n'  => ['en' => $b['en'], 'km' => $b['km']],
                    'sort_order' => $i,
                    'is_active'  => true,
                ],
            );
        }

        // Attach brands to products in the Electronics category subtree (and descendants).
        $electronics = Category::where('slug', 'electronics')->first();

        if ($electronics === null) {
            return;
        }

        $categoryIds = $electronics->allDescendantIds();

        $products = Product::whereIn('category_id', $categoryIds)
            ->whereNull('brand_id')
            ->get();

        $brandKeys = array_keys($created);

        foreach ($products as $index => $product) {
            // Round-robin assignment so every brand shows up in the UI.
            $brandSlug = $brandKeys[$index % count($brandKeys)];
            $product->update(['brand_id' => $created[$brandSlug]->id]);
        }
    }
}
