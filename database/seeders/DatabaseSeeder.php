<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Users
        User::firstOrCreate(
            ['email' => 'admin@corasoft.com'],
            ['name' => 'Admin', 'password' => bcrypt('password'), 'role' => 'admin', 'email_verified_at' => now()]
        );

        $vendor1 = User::firstOrCreate(
            ['email' => 'vendor@corasoft.com'],
            ['name' => 'Sopheap Chan', 'password' => bcrypt('password'), 'role' => 'vendor_owner', 'email_verified_at' => now()]
        );

        $vendor2 = User::firstOrCreate(
            ['email' => 'vendor2@corasoft.com'],
            ['name' => 'Dara Keo', 'password' => bcrypt('password'), 'role' => 'vendor_owner', 'email_verified_at' => now()]
        );

        $vendor3 = User::firstOrCreate(
            ['email' => 'vendor3@corasoft.com'],
            ['name' => 'Sreymom Lim', 'password' => bcrypt('password'), 'role' => 'vendor_owner', 'email_verified_at' => now()]
        );

        User::firstOrCreate(
            ['email' => 'buyer@corasoft.com'],
            ['name' => 'Buyer Demo', 'password' => bcrypt('password'), 'role' => 'buyer', 'email_verified_at' => now()]
        );

        // Categories
        $categoryData = [
            ['en' => 'Electronics',  'km' => 'អេឡិចត្រូនិច'],
            ['en' => 'Fashion',      'km' => 'សម្លៀកបំពាក់'],
            ['en' => 'Home & Living','km' => 'គ្រឿងសង្ហារឹម'],
            ['en' => 'Beauty',       'km' => 'សម្រស់'],
            ['en' => 'Sports',       'km' => 'កីឡា'],
            ['en' => 'Food',         'km' => 'អាហារ'],
        ];

        $cats = [];
        foreach ($categoryData as $i => $c) {
            $cats[] = Category::firstOrCreate(
                ['slug' => Str::slug($c['en'])],
                ['name_i18n' => $c, 'sort_order' => $i, 'is_active' => true]
            );
        }

        // Shop 1 — Electronics & Fashion
        $shop1 = Shop::firstOrCreate(
            ['slug' => 'phnom-penh-electronics'],
            ['owner_id' => $vendor1->id, 'name' => 'Phnom Penh Electronics', 'email' => 'shop@ppelectronics.com', 'phone' => '+855 12 345 678', 'description_i18n' => ['en' => 'Your #1 destination for tech and gadgets in Cambodia. Authorized dealer for premium brands.', 'km' => ''], 'address' => ['city' => 'Phnom Penh', 'district' => 'Daun Penh'], 'status' => 'active', 'commission_percent' => 8]
        );

        $vendor1->update(['shop_id' => $shop1->id]);
        $this->seedElectronics($shop1->id, $cats);

        // Shop 2 — Fashion & Beauty
        $shop2 = Shop::firstOrCreate(
            ['slug' => 'angkor-fashion-house'],
            ['owner_id' => $vendor2->id, 'name' => 'Angkor Fashion House', 'email' => 'hello@angkorfashion.com', 'phone' => '+855 77 888 999', 'description_i18n' => ['en' => 'Traditional Khmer textiles meets modern fashion. Handmade scarves, silk garments and accessories.', 'km' => ''], 'address' => ['city' => 'Siem Reap', 'district' => 'Siem Reap Town'], 'status' => 'active', 'commission_percent' => 8]
        );

        $vendor2->update(['shop_id' => $shop2->id]);
        $this->seedFashion($shop2->id, $cats);

        // Shop 3 — Food & Home
        $shop3 = Shop::firstOrCreate(
            ['slug' => 'kampot-farms-direct'],
            ['owner_id' => $vendor3->id, 'name' => 'Kampot Farms Direct', 'email' => 'order@kampotfarms.com', 'phone' => '+855 96 111 222', 'description_i18n' => ['en' => 'Farm-to-table Cambodian specialty products. Premium Kampot pepper, organic teas, and artisan home goods.', 'km' => ''], 'address' => ['city' => 'Kampot', 'district' => 'Kampot Town'], 'status' => 'active', 'commission_percent' => 8]
        );

        $vendor3->update(['shop_id' => $shop3->id]);
        $this->seedFoodAndHome($shop3->id, $cats);

        // Vendor 4
        $vendor4 = User::firstOrCreate(
            ['email' => 'vendor4@corasoft.com'],
            ['name' => 'Bopha Noun', 'password' => bcrypt('password'), 'role' => 'vendor_owner', 'email_verified_at' => now()]
        );

        $shop4 = Shop::firstOrCreate(
            ['slug' => 'tonle-sap-beauty'],
            ['owner_id' => $vendor4->id, 'name' => 'Tonle Sap Beauty', 'email' => 'hi@tonlesapbeauty.com', 'phone' => '+855 93 222 333', 'description_i18n' => ['en' => 'Natural Cambodian beauty essentials using lotus, coconut, and jasmine extracts.', 'km' => ''], 'address' => ['city' => 'Battambang', 'district' => 'Battambang Town'], 'status' => 'active', 'commission_percent' => 8]
        );
        $vendor4->update(['shop_id' => $shop4->id]);
        $this->seedBeautyShop($shop4->id, $cats);

        // Vendor 5
        $vendor5 = User::firstOrCreate(
            ['email' => 'vendor5@corasoft.com'],
            ['name' => 'Ratha Pich', 'password' => bcrypt('password'), 'role' => 'vendor_owner', 'email_verified_at' => now()]
        );

        $shop5 = Shop::firstOrCreate(
            ['slug' => 'mekong-sports-hub'],
            ['owner_id' => $vendor5->id, 'name' => 'Mekong Sports Hub', 'email' => 'sales@mekongsporthub.com', 'phone' => '+855 81 444 555', 'description_i18n' => ['en' => 'Official sports gear and outdoor equipment for every athlete in Cambodia.', 'km' => ''], 'address' => ['city' => 'Phnom Penh', 'district' => 'Toul Kork'], 'status' => 'active', 'commission_percent' => 8]
        );
        $vendor5->update(['shop_id' => $shop5->id]);
        $this->seedSportsShop($shop5->id, $cats);

        // Vendor 6
        $vendor6 = User::firstOrCreate(
            ['email' => 'vendor6@corasoft.com'],
            ['name' => 'Kosal Meas', 'password' => bcrypt('password'), 'role' => 'vendor_owner', 'email_verified_at' => now()]
        );

        $shop6 = Shop::firstOrCreate(
            ['slug' => 'siem-reap-crafts'],
            ['owner_id' => $vendor6->id, 'name' => 'Siem Reap Crafts', 'email' => 'craft@siemreapcrafts.com', 'phone' => '+855 70 666 777', 'description_i18n' => ['en' => 'Handcrafted wooden sculptures, silverware, and Apsara-inspired home décor.', 'km' => ''], 'address' => ['city' => 'Siem Reap', 'district' => 'Angkor'], 'status' => 'active', 'commission_percent' => 8]
        );
        $vendor6->update(['shop_id' => $shop6->id]);
        $this->seedCraftsShop($shop6->id, $cats);

        // Brands — created after products so we can attach brand_id to electronics.
        $this->call(BrandsSeeder::class);
    }

    private function seedElectronics(int $shopId, array $cats): void
    {
        $products = [
            [
                'name' => ['en' => 'Wireless Earbuds Pro', 'km' => 'កាស Wireless'],
                'desc' => ['en' => 'Premium sound quality with 30hr battery life. Noise cancellation built in.', 'km' => ''],
                'cat' => 0, 'featured' => true,
                'variants' => [
                    ['sku' => 'EAR-BLK', 'options' => ['Color' => 'Black'], 'price' => 2999, 'stock' => 50],
                    ['sku' => 'EAR-WHT', 'options' => ['Color' => 'White'], 'price' => 2999, 'stock' => 30],
                ],
            ],
            [
                'name' => ['en' => 'Smart Watch Series 5', 'km' => 'នាឡិកា Smart'],
                'desc' => ['en' => 'Track fitness, receive notifications, and monitor health metrics.', 'km' => ''],
                'cat' => 0, 'featured' => true,
                'variants' => [
                    ['sku' => 'SW-42MM', 'options' => ['Size' => '42mm'], 'price' => 8900, 'stock' => 20],
                    ['sku' => 'SW-46MM', 'options' => ['Size' => '46mm'], 'price' => 9900, 'stock' => 15],
                ],
            ],
            [
                'name' => ['en' => 'Portable Power Bank 20000mAh', 'km' => 'ថ្ម Power Bank'],
                'desc' => ['en' => 'Fast-charge 3 devices simultaneously. LED display for accurate battery level.', 'km' => ''],
                'cat' => 0, 'featured' => true,
                'variants' => [
                    ['sku' => 'PB-BLK', 'options' => ['Color' => 'Black'], 'price' => 1899, 'stock' => 100],
                    ['sku' => 'PB-WHT', 'options' => ['Color' => 'White'], 'price' => 1899, 'stock' => 80],
                ],
            ],
            [
                'name' => ['en' => 'Bluetooth Speaker Mini', 'km' => 'វ៉ាគីBluetooth'],
                'desc' => ['en' => 'Waterproof IPX7, 360° surround sound, 12hr playtime.', 'km' => ''],
                'cat' => 0, 'featured' => false,
                'variants' => [
                    ['sku' => 'SPK-BLK', 'options' => ['Color' => 'Black'], 'price' => 3500, 'stock' => 40],
                    ['sku' => 'SPK-RED', 'options' => ['Color' => 'Red'], 'price' => 3500, 'stock' => 25],
                    ['sku' => 'SPK-BLU', 'options' => ['Color' => 'Blue'], 'price' => 3500, 'stock' => 30],
                ],
            ],
            [
                'name' => ['en' => 'USB-C Fast Charging Hub (7-in-1)', 'km' => 'ហាប់ USB-C'],
                'desc' => ['en' => '7-port hub: HDMI 4K, 3×USB-A, SD, microSD, USB-C PD 100W. Compatible with all laptops.', 'km' => ''],
                'cat' => 0, 'featured' => true,
                'variants' => [
                    ['sku' => 'HUB-7IN1-GRY', 'options' => ['Color' => 'Space Grey'], 'price' => 2499, 'stock' => 60],
                    ['sku' => 'HUB-7IN1-SLV', 'options' => ['Color' => 'Silver'],      'price' => 2499, 'stock' => 40],
                ],
            ],
            [
                'name' => ['en' => 'Ring Light 10-inch with Tripod', 'km' => 'ភ្លើង Ring Light'],
                'desc' => ['en' => 'Adjustable color temperature (3000–6000K), 10 brightness levels. Perfect for streaming and photography.', 'km' => ''],
                'cat' => 0, 'featured' => false,
                'variants' => [
                    ['sku' => 'RL-10IN', 'options' => ['Size' => '10 inch'], 'price' => 3200, 'stock' => 35],
                ],
            ],
        ];

        $this->createProducts($shopId, $products, $cats);
    }

    private function seedFashion(int $shopId, array $cats): void
    {
        $products = [
            [
                'name' => ['en' => 'Khmer Silk Scarf', 'km' => 'កន្សែងសូត្រខ្មែរ'],
                'desc' => ['en' => 'Handwoven traditional Khmer silk. Each piece is unique and made by artisans in Siem Reap.', 'km' => ''],
                'cat' => 1, 'featured' => true,
                'variants' => [
                    ['sku' => 'SILK-RED', 'options' => ['Color' => 'Red'], 'price' => 1500, 'stock' => 100],
                    ['sku' => 'SILK-BLU', 'options' => ['Color' => 'Blue'], 'price' => 1500, 'stock' => 80],
                    ['sku' => 'SILK-GLD', 'options' => ['Color' => 'Gold'], 'price' => 1800, 'stock' => 60],
                ],
            ],
            [
                'name' => ['en' => 'Traditional Krama Cotton', 'km' => 'ក្រមារូបថត'],
                'desc' => ['en' => 'Authentic Cambodian krama scarf. Versatile multi-use cloth worn across Cambodia for generations.', 'km' => ''],
                'cat' => 1, 'featured' => true,
                'variants' => [
                    ['sku' => 'KRM-BLR', 'options' => ['Pattern' => 'Blue & Red'], 'price' => 800, 'stock' => 200],
                    ['sku' => 'KRM-WBK', 'options' => ['Pattern' => 'White & Black'], 'price' => 800, 'stock' => 150],
                ],
            ],
            [
                'name' => ['en' => 'Natural Face Serum', 'km' => 'សេរ៉ូមមុខធម្មជាតិ'],
                'desc' => ['en' => 'Brightening and anti-aging formula with local botanical extracts.', 'km' => ''],
                'cat' => 3, 'featured' => true,
                'variants' => [
                    ['sku' => 'SER-30ML', 'options' => ['Size' => '30ml'], 'price' => 3500, 'stock' => 200],
                    ['sku' => 'SER-60ML', 'options' => ['Size' => '60ml'], 'price' => 5900, 'stock' => 120],
                ],
            ],
            [
                'name' => ['en' => 'Lotus Flower Body Lotion', 'km' => 'លីម Lotus'],
                'desc' => ['en' => 'Enriched with Cambodian lotus extract. Deeply moisturising, fast absorbing.', 'km' => ''],
                'cat' => 3, 'featured' => false,
                'variants' => [
                    ['sku' => 'LOT-200', 'options' => ['Size' => '200ml'], 'price' => 1200, 'stock' => 300],
                ],
            ],
            [
                'name' => ['en' => 'Khmer Batik Wrap Dress', 'km' => 'អាវវ៉ែបាទីក'],
                'desc' => ['en' => 'Flowy wrap dress in hand-stamped Cambodian batik pattern. One-size-fits-most.', 'km' => ''],
                'cat' => 1, 'featured' => true,
                'variants' => [
                    ['sku' => 'BATIK-BLU', 'options' => ['Color' => 'Indigo Blue'], 'price' => 3800, 'stock' => 60],
                    ['sku' => 'BATIK-GRN', 'options' => ['Color' => 'Forest Green'], 'price' => 3800, 'stock' => 45],
                    ['sku' => 'BATIK-TER', 'options' => ['Color' => 'Terracotta'],   'price' => 3800, 'stock' => 30],
                ],
            ],
            [
                'name' => ['en' => 'Coconut Shell Earrings Set', 'km' => 'ក្តាស់ស្លាបព្រាដូង'],
                'desc' => ['en' => 'Lightweight hand-polished coconut shell earrings. Set of 3 styles.', 'km' => ''],
                'cat' => 1, 'featured' => false,
                'variants' => [
                    ['sku' => 'EAR-SET3-NAT', 'options' => ['Finish' => 'Natural'],  'price' => 950,  'stock' => 150],
                    ['sku' => 'EAR-SET3-BLK', 'options' => ['Finish' => 'Lacquered Black'], 'price' => 1100, 'stock' => 80],
                ],
            ],
        ];

        $this->createProducts($shopId, $products, $cats);
    }

    private function seedFoodAndHome(int $shopId, array $cats): void
    {
        $products = [
            [
                'name' => ['en' => 'Kampot Pepper Gift Set', 'km' => 'ម្រេចកំពត'],
                'desc' => ['en' => 'Premium Cambodian Kampot pepper — black, red, and white. GI-certified, direct from the farm.', 'km' => ''],
                'cat' => 5, 'featured' => true,
                'variants' => [
                    ['sku' => 'PEP-BLK', 'options' => ['Type' => 'Black'], 'price' => 2200, 'stock' => 500],
                    ['sku' => 'PEP-RED', 'options' => ['Type' => 'Red'], 'price' => 2500, 'stock' => 300],
                    ['sku' => 'PEP-WHT', 'options' => ['Type' => 'White'], 'price' => 2800, 'stock' => 200],
                    ['sku' => 'PEP-SET', 'options' => ['Type' => 'Mixed Set (3 types)'], 'price' => 6900, 'stock' => 150],
                ],
            ],
            [
                'name' => ['en' => 'Organic Cambodian Tea', 'km' => 'តែកម្ពុជា'],
                'desc' => ['en' => 'Hand-picked from highland gardens. Lemongrass, ginger, and pandan blend.', 'km' => ''],
                'cat' => 5, 'featured' => true,
                'variants' => [
                    ['sku' => 'TEA-50G', 'options' => ['Weight' => '50g'], 'price' => 900, 'stock' => 400],
                    ['sku' => 'TEA-150G', 'options' => ['Weight' => '150g'], 'price' => 2400, 'stock' => 200],
                ],
            ],
            [
                'name' => ['en' => 'Bamboo Coffee Table', 'km' => 'តុកាហ្វេបាំបូ'],
                'desc' => ['en' => 'Eco-friendly sustainable bamboo furniture. Handcrafted by local artisans.', 'km' => ''],
                'cat' => 2, 'featured' => true,
                'variants' => [
                    ['sku' => 'TBL-SM', 'options' => ['Size' => 'Small (60cm)'], 'price' => 12000, 'stock' => 10],
                    ['sku' => 'TBL-LG', 'options' => ['Size' => 'Large (90cm)'], 'price' => 18000, 'stock' => 5],
                ],
            ],
            [
                'name' => ['en' => 'Rattan Storage Basket Set', 'km' => 'ល្អោង Rattan'],
                'desc' => ['en' => 'Set of 3 handwoven rattan baskets. Perfect for home organization.', 'km' => ''],
                'cat' => 2, 'featured' => false,
                'variants' => [
                    ['sku' => 'BSK-NAT', 'options' => ['Color' => 'Natural'], 'price' => 4500, 'stock' => 30],
                    ['sku' => 'BSK-BLK', 'options' => ['Color' => 'Black'], 'price' => 4800, 'stock' => 20],
                ],
            ],
            [
                'name' => ['en' => 'Dried Mango Slices (500g)', 'km' => 'ស្វាយស្ងួត'],
                'desc' => ['en' => 'Sun-dried premium Cambodian mango. No added sugar or preservatives. Packed fresh.', 'km' => ''],
                'cat' => 5, 'featured' => true,
                'variants' => [
                    ['sku' => 'MANGO-250G', 'options' => ['Weight' => '250g'], 'price' => 650,  'stock' => 300],
                    ['sku' => 'MANGO-500G', 'options' => ['Weight' => '500g'], 'price' => 1150, 'stock' => 200],
                ],
            ],
            [
                'name' => ['en' => 'Handmade Coconut Wax Candle', 'km' => 'ទៀនខ្លាញ់ដូង'],
                'desc' => ['en' => 'Natural coconut wax candle with jasmine & pandan scent. 40hr burn time.', 'km' => ''],
                'cat' => 2, 'featured' => false,
                'variants' => [
                    ['sku' => 'CNDL-S', 'options' => ['Size' => 'Small (150g)'], 'price' => 1400, 'stock' => 100],
                    ['sku' => 'CNDL-L', 'options' => ['Size' => 'Large (300g)'], 'price' => 2600, 'stock' => 60],
                ],
            ],
        ];

        $this->createProducts($shopId, $products, $cats);
    }

    private function seedBeautyShop(int $shopId, array $cats): void
    {
        $products = [
            [
                'name' => ['en' => 'Jasmine & Coconut Face Oil', 'km' => 'ប្រេងមុខ Jasmine'],
                'desc' => ['en' => 'Cold-pressed coconut oil infused with jasmine. Nourishes and brightens skin overnight.', 'km' => ''],
                'cat' => 3, 'featured' => true,
                'variants' => [
                    ['sku' => 'FCO-30ML', 'options' => ['Size' => '30ml'], 'price' => 2800, 'stock' => 150],
                    ['sku' => 'FCO-60ML', 'options' => ['Size' => '60ml'], 'price' => 4900, 'stock' => 80],
                ],
            ],
            [
                'name' => ['en' => 'Lotus Extract Lip Balm Set', 'km' => 'ថ្នាំបបូរមាត់ Lotus'],
                'desc' => ['en' => 'Set of 5 tinted lip balms with SPF 20. Cambodian lotus petal extract formula.', 'km' => ''],
                'cat' => 3, 'featured' => true,
                'variants' => [
                    ['sku' => 'LIP-SET5', 'options' => ['Pack' => '5 pcs'], 'price' => 1500, 'stock' => 300],
                ],
            ],
            [
                'name' => ['en' => 'Rice Water Hair Mask', 'km' => 'ម៉ាស់សក់ទឹកអង្ករ'],
                'desc' => ['en' => 'Deep conditioning mask with fermented rice water. Strengthens and adds shine.', 'km' => ''],
                'cat' => 3, 'featured' => false,
                'variants' => [
                    ['sku' => 'HAIR-200G', 'options' => ['Weight' => '200g'], 'price' => 1900, 'stock' => 200],
                    ['sku' => 'HAIR-400G', 'options' => ['Weight' => '400g'], 'price' => 3200, 'stock' => 100],
                ],
            ],
            [
                'name' => ['en' => 'Turmeric Brightening Soap', 'km' => 'សាប៊ូរមៀត'],
                'desc' => ['en' => 'Handmade with wild Cambodian turmeric. Evens skin tone and removes blemishes naturally.', 'km' => ''],
                'cat' => 3, 'featured' => false,
                'variants' => [
                    ['sku' => 'SOAP-100G', 'options' => ['Weight' => '100g'], 'price' => 600, 'stock' => 500],
                    ['sku' => 'SOAP-3PK', 'options' => ['Pack' => '3 bars'], 'price' => 1600, 'stock' => 200],
                ],
            ],
            [
                'name' => ['en' => 'Aloe Vera Soothing Gel', 'km' => 'ជែលAloe Vera'],
                'desc' => ['en' => 'Pure 98% Cambodian aloe vera gel. Relieves sunburn, hydrates and calms irritated skin.', 'km' => ''],
                'cat' => 3, 'featured' => true,
                'variants' => [
                    ['sku' => 'ALOE-100ML', 'options' => ['Size' => '100ml'], 'price' => 980,  'stock' => 250],
                    ['sku' => 'ALOE-250ML', 'options' => ['Size' => '250ml'], 'price' => 1900, 'stock' => 150],
                ],
            ],
            [
                'name' => ['en' => 'Charcoal Detox Face Mask', 'km' => 'ម៉ាស់មុខ Charcoal'],
                'desc' => ['en' => 'Deep-cleansing activated charcoal mask. Unclogs pores and removes excess oil in 15 minutes.', 'km' => ''],
                'cat' => 3, 'featured' => false,
                'variants' => [
                    ['sku' => 'MASK-75G', 'options' => ['Weight' => '75g'], 'price' => 1350, 'stock' => 180],
                ],
            ],
        ];

        $this->createProducts($shopId, $products, $cats);
    }

    private function seedSportsShop(int $shopId, array $cats): void
    {
        $products = [
            [
                'name' => ['en' => 'Pro Football (Size 5)', 'km' => 'បាល់ទាត់ Pro'],
                'desc' => ['en' => 'FIFA-approved match ball. Thermally bonded panels for consistent shape and flight.', 'km' => ''],
                'cat' => 4, 'featured' => true,
                'variants' => [
                    ['sku' => 'BALL-S5-WHT', 'options' => ['Color' => 'White/Black'], 'price' => 3500, 'stock' => 80],
                    ['sku' => 'BALL-S5-RED', 'options' => ['Color' => 'Red/Black'],  'price' => 3500, 'stock' => 60],
                ],
            ],
            [
                'name' => ['en' => 'Badminton Racket Set', 'km' => 'កង់ Badminton'],
                'desc' => ['en' => 'Lightweight carbon frame set (2 rackets + 3 shuttlecocks + carry bag).', 'km' => ''],
                'cat' => 4, 'featured' => true,
                'variants' => [
                    ['sku' => 'BAD-SET-BLU', 'options' => ['Color' => 'Blue'], 'price' => 4800, 'stock' => 50],
                    ['sku' => 'BAD-SET-RED', 'options' => ['Color' => 'Red'],  'price' => 4800, 'stock' => 40],
                ],
            ],
            [
                'name' => ['en' => 'Yoga Mat 6mm Non-slip', 'km' => 'ម៉ាត់យូហ្គា'],
                'desc' => ['en' => 'Extra-thick eco-friendly TPE yoga mat. Anti-slip texture on both sides.', 'km' => ''],
                'cat' => 4, 'featured' => true,
                'variants' => [
                    ['sku' => 'YOGA-PUR', 'options' => ['Color' => 'Purple'],  'price' => 2200, 'stock' => 120],
                    ['sku' => 'YOGA-GRN', 'options' => ['Color' => 'Green'],   'price' => 2200, 'stock' => 100],
                    ['sku' => 'YOGA-BLK', 'options' => ['Color' => 'Black'],   'price' => 2200, 'stock' => 90],
                ],
            ],
            [
                'name' => ['en' => 'Resistance Bands Set (5-pack)', 'km' => 'កំណប់ Resistance Band'],
                'desc' => ['en' => '5 resistance levels from 10–40 lbs. Latex-free, suitable for all fitness levels.', 'km' => ''],
                'cat' => 4, 'featured' => false,
                'variants' => [
                    ['sku' => 'RB-5PK', 'options' => ['Pack' => '5 bands'], 'price' => 1800, 'stock' => 200],
                ],
            ],
            [
                'name' => ['en' => 'Running Shoes (Unisex)', 'km' => 'ស្បែកជើងរត់'],
                'desc' => ['en' => 'Lightweight mesh upper with cushioned EVA sole. Ideal for road running and gym training.', 'km' => ''],
                'cat' => 4, 'featured' => true,
                'variants' => [
                    ['sku' => 'SHOE-39-BLK', 'options' => ['Size' => '39', 'Color' => 'Black'], 'price' => 6500, 'stock' => 30],
                    ['sku' => 'SHOE-40-BLK', 'options' => ['Size' => '40', 'Color' => 'Black'], 'price' => 6500, 'stock' => 30],
                    ['sku' => 'SHOE-41-BLK', 'options' => ['Size' => '41', 'Color' => 'Black'], 'price' => 6500, 'stock' => 25],
                    ['sku' => 'SHOE-42-BLK', 'options' => ['Size' => '42', 'Color' => 'Black'], 'price' => 6500, 'stock' => 20],
                    ['sku' => 'SHOE-43-WHT', 'options' => ['Size' => '43', 'Color' => 'White'], 'price' => 6500, 'stock' => 15],
                ],
            ],
            [
                'name' => ['en' => 'Gym Gloves with Wrist Support', 'km' => 'ស្រោមមើម Gym'],
                'desc' => ['en' => 'Anti-slip palm grip, adjustable wrist wrap. Suitable for weightlifting and crossfit.', 'km' => ''],
                'cat' => 4, 'featured' => false,
                'variants' => [
                    ['sku' => 'GLOVE-S',  'options' => ['Size' => 'S/M'], 'price' => 1600, 'stock' => 80],
                    ['sku' => 'GLOVE-L',  'options' => ['Size' => 'L/XL'], 'price' => 1600, 'stock' => 70],
                ],
            ],
        ];

        $this->createProducts($shopId, $products, $cats);
    }

    private function seedCraftsShop(int $shopId, array $cats): void
    {
        $products = [
            [
                'name' => ['en' => 'Apsara Wood Carving Decor', 'km' => 'ចម្លាក់អប្សរា'],
                'desc' => ['en' => 'Hand-carved teak wood Apsara dancer. A unique piece of Khmer cultural heritage for your home.', 'km' => ''],
                'cat' => 2, 'featured' => true,
                'variants' => [
                    ['sku' => 'APS-SM', 'options' => ['Size' => 'Small (25cm)'],  'price' => 8500,  'stock' => 15],
                    ['sku' => 'APS-LG', 'options' => ['Size' => 'Large (50cm)'],  'price' => 22000, 'stock' => 5],
                ],
            ],
            [
                'name' => ['en' => 'Silver Angkor Pendant Necklace', 'km' => 'ខ្សែក Angkor ប្រាក់'],
                'desc' => ['en' => '925 sterling silver pendant shaped as Angkor Wat. Comes with 45cm chain.', 'km' => ''],
                'cat' => 1, 'featured' => true,
                'variants' => [
                    ['sku' => 'NECK-SLV', 'options' => ['Material' => 'Sterling Silver'], 'price' => 5500, 'stock' => 40],
                    ['sku' => 'NECK-GLD', 'options' => ['Material' => 'Gold-plated'],     'price' => 7200, 'stock' => 20],
                ],
            ],
            [
                'name' => ['en' => 'Handwoven Palm Leaf Tray', 'km' => 'ថាស ស្លឹករនុក'],
                'desc' => ['en' => 'Eco-friendly tray handwoven by Cambodian craftswomen. Perfect for fruits or décor.', 'km' => ''],
                'cat' => 2, 'featured' => false,
                'variants' => [
                    ['sku' => 'TRAY-SM', 'options' => ['Size' => 'Small (30cm)'],  'price' => 1200, 'stock' => 60],
                    ['sku' => 'TRAY-LG', 'options' => ['Size' => 'Large (45cm)'],  'price' => 2100, 'stock' => 35],
                ],
            ],
            [
                'name' => ['en' => 'Ceramic Rice Bowl Set (4 pcs)', 'km' => 'ចានអង្ករ Ceramic'],
                'desc' => ['en' => 'Hand-painted ceramic bowls with Khmer lotus motif. Microwave and dishwasher safe.', 'km' => ''],
                'cat' => 2, 'featured' => false,
                'variants' => [
                    ['sku' => 'BOWL-4PK-WHT', 'options' => ['Color' => 'White/Blue'], 'price' => 3800, 'stock' => 45],
                    ['sku' => 'BOWL-4PK-GRN', 'options' => ['Color' => 'Green'],      'price' => 3800, 'stock' => 30],
                ],
            ],
            [
                'name' => ['en' => 'Macramé Wall Hanging', 'km' => 'ម៉ាក្រូម៉េ ជញ្ជាំង'],
                'desc' => ['en' => 'Boho-style handknotted macramé. Natural cotton rope, 60×90cm. Ready to hang.', 'km' => ''],
                'cat' => 2, 'featured' => true,
                'variants' => [
                    ['sku' => 'MAC-NAT', 'options' => ['Color' => 'Natural White'], 'price' => 2800, 'stock' => 40],
                    ['sku' => 'MAC-BEI', 'options' => ['Color' => 'Beige'],         'price' => 2800, 'stock' => 25],
                ],
            ],
            [
                'name' => ['en' => 'Khmer Lacquerware Trinket Box', 'km' => 'ប្រអប់លាក'],
                'desc' => ['en' => 'Traditional Cambodian lacquerware box with hand-painted Angkor motif. Perfect gift.', 'km' => ''],
                'cat' => 2, 'featured' => false,
                'variants' => [
                    ['sku' => 'LAC-SM', 'options' => ['Size' => 'Small (10cm)'],  'price' => 1600, 'stock' => 50],
                    ['sku' => 'LAC-LG', 'options' => ['Size' => 'Large (20cm)'],  'price' => 3200, 'stock' => 25],
                ],
            ],
        ];

        $this->createProducts($shopId, $products, $cats);
    }

    /** @param array<int, mixed> $products */
    private function createProducts(int $shopId, array $products, array $cats): void
    {
        foreach ($products as $i => $p) {
            $slug = Str::slug($p['name']['en']);
            $product = Product::firstOrCreate(
                ['shop_id' => $shopId, 'slug' => $slug],
                ['category_id' => $cats[$p['cat']]->id, 'name_i18n' => $p['name'], 'description_i18n' => $p['desc'], 'status' => 'active', 'is_featured' => $p['featured']]
            );

            foreach ($p['variants'] as $v) {
                ProductVariant::firstOrCreate(
                    ['sku' => $v['sku']],
                    ['product_id' => $product->id, 'options' => $v['options'], 'price_cents' => $v['price'], 'price_currency' => 'USD', 'stock_quantity' => $v['stock'], 'is_active' => true]
                );
            }
        }
    }
}
