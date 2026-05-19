<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class PgMarketImportSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'vendor@corasoft.dev'],
            [
                'name'     => 'PG Market Vendor',
                'password' => Hash::make('password'),
                'role'     => 'vendor_owner',
            ]
        );

        $shop = Shop::firstOrCreate(
            ['slug' => 'ata-shop'],
            [
                'owner_id'         => $user->id,
                'name'             => 'ATA Shop',
                'description_i18n' => [
                    'en' => 'Wholesale and retail shop for all types of car accessories and auto parts.',
                    'km' => 'ហាងលក់ដូរ និងរាយ គ្រឿងបន្លាស់រថយន្តគ្រប់ប្រភេទ',
                ],
                'phone'            => '012 345 67 89',
                'address'          => [
                    'street'   => 'ផ្ទះ148, ផ្លូវ148',
                    'district' => 'សង្កាត់ស្វាតណ្តាលទី2',
                    'city'     => 'ខណ្ឌដូនពេញ, រាជធានីភ្នំពេញ',
                    'country'  => 'KH',
                ],
                'logo'             => 'https://images.unsplash.com/photo-1599256871679-6a4996d1d7ef?w=200&q=80',
                'banner'           => 'https://images.unsplash.com/photo-1520340356584-f9917d1eea6f?w=1200&q=80',
                'status'           => 'active',
                'currency'         => 'USD',
            ]
        );

        $category = Category::firstOrCreate(
            ['slug' => 'automotive'],
            [
                'name_i18n'  => ['en' => 'Automotive', 'km' => 'រថយន្ត'],
                'is_active'  => true,
                'sort_order' => 10,
            ]
        );

        $product = Product::firstOrCreate(
            ['slug' => 'thinkcar-scanmate'],
            [
                'shop_id'          => $shop->id,
                'category_id'      => $category->id,
                'name_i18n'        => [
                    'en' => 'Thinkcar Scanmate',
                    'km' => 'ឧបករណ៍ Thinkcar Scanmate',
                ],
                'description_i18n' => [
                    'en' => "OBD2 Bluetooth car diagnostic scanner. Works on all 12V vehicles. Reads and clears engine fault codes in 5 categories: Engine, ABS, Airbag, Transmission and more. Compatible with iOS, Android and desktop. Contact: Telegram 086991216",
                    'km' => "ចិត​បង្ករ Bluetooth ប្រព័ន្ធ​ compat ​ ​ ​ ​ ​ ​ ​ ​ ​ 12V ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ 5 ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ ​ 086991216",
                ],
                'status'           => 'active',
                'is_featured'      => false,
                'images'           => ['https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=600&q=80'],
            ]
        );

        ProductVariant::firstOrCreate(
            ['sku' => 'THINKCAR-SCANMATE-001'],
            [
                'product_id'     => $product->id,
                'price_cents'    => 6000,
                'price_currency' => 'USD',
                'stock_quantity' => 10,
                'is_active'      => true,
            ]
        );

        $this->command->info("Imported: Shop [{$shop->name}], Product [{$product->name_i18n['en']}]");
    }
}
