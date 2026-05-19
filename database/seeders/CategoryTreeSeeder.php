<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class CategoryTreeSeeder extends Seeder
{
    public function run(): void
    {
        $tree = $this->getCategoryTree();
        $order = 0;

        foreach ($tree as $root) {
            $order++;
            $this->createCategory($root, null, $order);
        }

        $this->command->info('Category tree seeded: ' . Category::count() . ' total categories.');
    }

    private function createCategory(array $data, ?int $parentId, int $sortOrder): void
    {
        $slug = Str::slug($data['en']);

        // Skip if already exists
        if (Category::where('slug', $slug)->exists()) {
            $existing = Category::where('slug', $slug)->first();
            $this->command->warn("  Skip (exists): {$data['en']}");

            // Still create children under existing
            if (!empty($data['children'])) {
                $childOrder = 0;
                foreach ($data['children'] as $child) {
                    $childOrder++;
                    $this->createCategory($child, $existing->id, $childOrder);
                }
            }
            return;
        }

        $category = Category::create([
            'parent_id' => $parentId,
            'name_i18n' => ['en' => $data['en'], 'km' => $data['km']],
            'slug' => $slug,
            'is_active' => true,
            'sort_order' => $sortOrder,
        ]);

        $prefix = $parentId ? '  └─ ' : '● ';
        $this->command->info("{$prefix}{$data['en']} ({$data['km']})");

        if (!empty($data['children'])) {
            $childOrder = 0;
            foreach ($data['children'] as $child) {
                $childOrder++;
                $this->createCategory($child, $category->id, $childOrder);
            }
        }
    }

    /**
     * @return list<array{en: string, km: string, children?: list<array{en: string, km: string, children?: list<array{en: string, km: string}>}>}>
     */
    private function getCategoryTree(): array
    {
        return [
            [
                'en' => 'Electronics', 'km' => 'អេឡិចត្រូនិច',
                'children' => [
                    ['en' => 'Smartphones', 'km' => 'ស្មាតហ្វូន', 'children' => [
                        ['en' => 'iPhone', 'km' => 'អាយហ្វូន'],
                        ['en' => 'Samsung', 'km' => 'សាមសុង'],
                        ['en' => 'Xiaomi', 'km' => 'សៀវមី'],
                        ['en' => 'OPPO', 'km' => 'អូប៉ូ'],
                        ['en' => 'Vivo', 'km' => 'វីវ៉ូ'],
                        ['en' => 'Realme', 'km' => 'រៀលមី'],
                    ]],
                    ['en' => 'Laptops & Computers', 'km' => 'កុំព្យូទ័រ', 'children' => [
                        ['en' => 'Laptops', 'km' => 'ឡេបថប'],
                        ['en' => 'Desktops', 'km' => 'កុំព្យូទ័រលើតុ'],
                        ['en' => 'Monitors', 'km' => 'ម៉ូនីទ័រ'],
                        ['en' => 'Computer Accessories', 'km' => 'បរិក្ខាកុំព្យូទ័រ'],
                    ]],
                    ['en' => 'Tablets', 'km' => 'ថេប្លេត'],
                    ['en' => 'Phone Accessories', 'km' => 'គ្រឿងបន្ថែមទូរសព្ទ', 'children' => [
                        ['en' => 'Phone Cases', 'km' => 'ស្រោមទូរសព្ទ'],
                        ['en' => 'Chargers & Cables', 'km' => 'សាកនិងខ្សែ'],
                        ['en' => 'Screen Protectors', 'km' => 'កញ្ចក់ការពារ'],
                        ['en' => 'Earphones & Headphones', 'km' => 'កាស'],
                        ['en' => 'Power Banks', 'km' => 'ប៊េតធើរីខាងក្រៅ'],
                    ]],
                    ['en' => 'Cameras', 'km' => 'កាមេរ៉ា'],
                    ['en' => 'Audio & Speakers', 'km' => 'អូឌីយ៉ូនិងឧបករណ៍បំពង'],
                    ['en' => 'Smart Watches', 'km' => 'នាឡិកាឆ្លាតវៃ'],
                    ['en' => 'Gaming', 'km' => 'ហ្គេម', 'children' => [
                        ['en' => 'Gaming Consoles', 'km' => 'ម៉ាស៊ីនហ្គេម'],
                        ['en' => 'Gaming Accessories', 'km' => 'គ្រឿងបន្ថែមហ្គេម'],
                    ]],
                    ['en' => 'Printers & Scanners', 'km' => 'ម៉ាស៊ីនព្រីនរូប'],
                    ['en' => 'Networking Equipment', 'km' => 'ឧបករណ៍បណ្តាញ'],
                    ['en' => 'Storage Devices', 'km' => 'ឧបករណ៍ផ្ទុកទិន្នន័យ'],
                ],
            ],
            [
                'en' => 'Fashion', 'km' => 'សម្លៀកបំពាក់',
                'children' => [
                    ['en' => "Men's Clothing", 'km' => 'សម្លៀកបំពាក់បុរស', 'children' => [
                        ['en' => 'T-Shirts', 'km' => 'អាវយឺត'],
                        ['en' => 'Shirts', 'km' => 'អាវឯក'],
                        ['en' => 'Pants', 'km' => 'ខោ'],
                        ['en' => 'Jeans', 'km' => 'ខោជីន'],
                        ['en' => 'Suits & Blazers', 'km' => 'ឈុតអាវកាក់'],
                        ['en' => 'Jackets', 'km' => 'អាវក្រៅ'],
                    ]],
                    ['en' => "Women's Clothing", 'km' => 'សម្លៀកបំពាក់នារី', 'children' => [
                        ['en' => 'Dresses', 'km' => 'សំពត់'],
                        ['en' => 'Tops & Blouses', 'km' => 'អាវនារី'],
                        ['en' => 'Skirts', 'km' => 'សំពត់ខ្លី'],
                        ['en' => 'Traditional Khmer Wear', 'km' => 'ឈុតខ្មែរ'],
                    ]],
                    ['en' => 'Shoes & Footwear', 'km' => 'ស្បែកជើង', 'children' => [
                        ['en' => "Men's Shoes", 'km' => 'ស្បែកជើងបុរស'],
                        ['en' => "Women's Shoes", 'km' => 'ស្បែកជើងនារី'],
                        ['en' => 'Sandals', 'km' => 'ស្បែកជើងរ៉ាក់'],
                        ['en' => 'Sports Shoes', 'km' => 'ស្បែកជើងកីឡា'],
                    ]],
                    ['en' => 'Bags & Wallets', 'km' => 'កាបូបនិងកាសិប'],
                    ['en' => 'Watches & Accessories', 'km' => 'នាឡិកានិងគ្រឿងផ្សេង'],
                    ['en' => 'Jewelry', 'km' => 'គ្រឿងអលង្ការ', 'children' => [
                        ['en' => 'Necklaces', 'km' => 'ខ្សែក'],
                        ['en' => 'Bracelets', 'km' => 'ខ្សែដៃ'],
                        ['en' => 'Earrings', 'km' => 'ក្រវិល'],
                        ['en' => 'Rings', 'km' => 'ចិញ្ចៀន'],
                    ]],
                    ['en' => 'Sunglasses', 'km' => 'វែនតាការពារថ្ងៃ'],
                    ['en' => 'Hats & Caps', 'km' => 'មួក'],
                ],
            ],
            [
                'en' => 'Beauty & Health', 'km' => 'សម្រស់និងសុខភាព',
                'children' => [
                    ['en' => 'Skincare', 'km' => 'ថែរក្សាស្បែក', 'children' => [
                        ['en' => 'Face Wash & Cleansers', 'km' => 'សម្អាតមុខ'],
                        ['en' => 'Moisturizers', 'km' => 'ក្រែមសើម'],
                        ['en' => 'Sunscreen', 'km' => 'ការពារកម្ដៅថ្ងៃ'],
                        ['en' => 'Serums', 'km' => 'សេរ៉ូម'],
                    ]],
                    ['en' => 'Makeup', 'km' => 'គ្រឿងសម្អាង', 'children' => [
                        ['en' => 'Foundation', 'km' => 'គ្រឹះមុខ'],
                        ['en' => 'Lipstick', 'km' => 'ក្រែមបបូរមាត់'],
                        ['en' => 'Eye Makeup', 'km' => 'សម្អាងភ្នែក'],
                        ['en' => 'Nail Care', 'km' => 'ថែរក្សាក្រចក'],
                    ]],
                    ['en' => 'Hair Care', 'km' => 'ថែរក្សាសក់'],
                    ['en' => 'Perfumes & Fragrances', 'km' => 'ទឹកអប់'],
                    ['en' => 'Personal Care', 'km' => 'ថែរក្សាខ្លួន'],
                    ['en' => 'Health Supplements', 'km' => 'វីតាមីនបន្ថែម'],
                    ['en' => 'Medical Supplies', 'km' => 'សម្ភារៈពេទ្យ'],
                ],
            ],
            [
                'en' => 'Home & Living', 'km' => 'គ្រឿងសង្ហារឹម',
                'children' => [
                    ['en' => 'Furniture', 'km' => 'គ្រឿងសង្ហារឹម', 'children' => [
                        ['en' => 'Sofas & Chairs', 'km' => 'កៅអី'],
                        ['en' => 'Tables & Desks', 'km' => 'តុ'],
                        ['en' => 'Beds & Mattresses', 'km' => 'គ្រែនិងពូក'],
                        ['en' => 'Shelves & Cabinets', 'km' => 'ធ្នើនិងទូ'],
                    ]],
                    ['en' => 'Kitchen & Dining', 'km' => 'គ្រឿងផ្ទះបាយ', 'children' => [
                        ['en' => 'Cookware', 'km' => 'ឆ្នាំងដាំ'],
                        ['en' => 'Kitchen Appliances', 'km' => 'ឧបករណ៍ផ្ទះបាយ'],
                        ['en' => 'Dinnerware', 'km' => 'ចាននិងពែង'],
                    ]],
                    ['en' => 'Home Decor', 'km' => 'ការតុបតែងផ្ទះ'],
                    ['en' => 'Bedding & Bath', 'km' => 'គ្រែនិងបន្ទប់ទឹក'],
                    ['en' => 'Lighting', 'km' => 'អំពូល'],
                    ['en' => 'Cleaning Supplies', 'km' => 'សម្ភារៈសម្អាត'],
                    ['en' => 'Storage & Organization', 'km' => 'ការរៀបចំនិងផ្ទុក'],
                ],
            ],
            [
                'en' => 'Food & Grocery', 'km' => 'ម្ហូបនិងគ្រឿងទេស',
                'children' => [
                    ['en' => 'Fresh Fruits', 'km' => 'ផ្លែឈើស្រស់'],
                    ['en' => 'Fresh Vegetables', 'km' => 'បន្លែស្រស់'],
                    ['en' => 'Meat & Seafood', 'km' => 'សាច់និងអាហារសមុទ្រ'],
                    ['en' => 'Rice & Grains', 'km' => 'អង្ករនិងគ្រាប់ធញ្ញជាតិ'],
                    ['en' => 'Spices & Condiments', 'km' => 'គ្រឿងទេស', 'children' => [
                        ['en' => 'Kampot Pepper', 'km' => 'ម្រេចកំពត'],
                        ['en' => 'Garlic', 'km' => 'ខ្ទឹមស'],
                        ['en' => 'Chili & Hot Sauce', 'km' => 'មា្ទេសនិងទឹកម្ទេស'],
                    ]],
                    ['en' => 'Snacks & Sweets', 'km' => 'ស្នែកនិងបង្អែម'],
                    ['en' => 'Canned & Packaged Food', 'km' => 'អាហារកំប៉ុង'],
                    ['en' => 'Cooking Oil', 'km' => 'ប្រេងឆា'],
                    ['en' => 'Noodles & Instant Food', 'km' => 'មីនិងអាហាររហ័ស'],
                    ['en' => 'Dairy Products', 'km' => 'ផលិតផលទឹកដោះគោ'],
                ],
            ],
            [
                'en' => 'Beverages', 'km' => 'ភេសជ្ជៈ',
                'children' => [
                    ['en' => 'Water', 'km' => 'ទឹកសុទ្ធ'],
                    ['en' => 'Soft Drinks', 'km' => 'ទឹកផ្អែម'],
                    ['en' => 'Coffee & Tea', 'km' => 'កាហ្វេនិងតែ'],
                    ['en' => 'Juice', 'km' => 'ទឹកផ្លែឈើ'],
                    ['en' => 'Energy Drinks', 'km' => 'ភេសជ្ជៈថាមពល'],
                    ['en' => 'Wine & Spirits', 'km' => 'ស្រានិងស្រាទំពាំង'],
                    ['en' => 'Beer', 'km' => 'ប៊ីយេរ'],
                ],
            ],
            [
                'en' => 'Sports & Outdoors', 'km' => 'កីឡានិងក្រៅផ្ទះ',
                'children' => [
                    ['en' => 'Exercise & Fitness', 'km' => 'ហាត់ប្រាណ'],
                    ['en' => 'Sportswear', 'km' => 'សម្លៀកបំពាក់កីឡា'],
                    ['en' => 'Cycling', 'km' => 'កង់'],
                    ['en' => 'Camping & Hiking', 'km' => 'បោះតង់'],
                    ['en' => 'Swimming', 'km' => 'ហែលទឹក'],
                    ['en' => 'Team Sports', 'km' => 'កីឡាក្រុម'],
                    ['en' => 'Yoga & Pilates', 'km' => 'យូហ្គា'],
                ],
            ],
            [
                'en' => 'Automotive & Spare Parts', 'km' => 'គ្រឿងបន្លាស់រថយន្ត',
                'children' => [
                    ['en' => 'Car Accessories', 'km' => 'គ្រឿងបន្ថែមរថយន្ត', 'children' => [
                        ['en' => 'Car Interior', 'km' => 'ខាងក្នុងរថយន្ត'],
                        ['en' => 'Car Exterior', 'km' => 'ខាងក្រៅរថយន្ត'],
                        ['en' => 'Car Electronics', 'km' => 'អេឡិចត្រូនិចរថយន្ត'],
                    ]],
                    ['en' => 'Engine Parts', 'km' => 'គ្រឿងម៉ាស៊ីន'],
                    ['en' => 'Brake Parts', 'km' => 'គ្រឿងហ្វ្រាំង'],
                    ['en' => 'Tires & Wheels', 'km' => 'កង់និងសង'],
                    ['en' => 'Car Care & Cleaning', 'km' => 'ថែរក្សារថយន្ត'],
                    ['en' => 'Motorcycle Parts', 'km' => 'គ្រឿងម៉ូតូ'],
                    ['en' => 'Oils & Lubricants', 'km' => 'ប្រេងម៉ាស៊ីន'],
                    ['en' => 'Diagnostic Tools', 'km' => 'ឧបករណ៍វិភាគ'],
                ],
            ],
            [
                'en' => 'Baby & Kids', 'km' => 'ទារកនិងកុមារ',
                'children' => [
                    ['en' => 'Baby Clothing', 'km' => 'សម្លៀកបំពាក់ទារក'],
                    ['en' => 'Diapers & Wipes', 'km' => 'ក្រណាត់រុំកូន'],
                    ['en' => 'Baby Feeding', 'km' => 'ការបំបៅទារក'],
                    ['en' => 'Strollers & Carriers', 'km' => 'រទេះរុញកូន'],
                    ['en' => 'Kids Clothing', 'km' => 'សម្លៀកបំពាក់កុមារ'],
                    ['en' => 'Toys & Games', 'km' => 'ប្រដាប់លេង', 'children' => [
                        ['en' => 'Educational Toys', 'km' => 'ប្រដាប់លេងអប់រំ'],
                        ['en' => 'Action Figures', 'km' => 'រូបតុក្កតា'],
                        ['en' => 'Building Blocks', 'km' => 'ប្លុកផ្គុំ'],
                        ['en' => 'Puzzles', 'km' => 'ផ្គុំរូប'],
                    ]],
                    ['en' => 'School Supplies', 'km' => 'សម្ភារៈសាលា'],
                ],
            ],
            [
                'en' => 'Arts & Crafts', 'km' => 'សិល្បៈនិងសិប្បកម្ម',
                'children' => [
                    ['en' => 'Khmer Handicrafts', 'km' => 'សិប្បកម្មខ្មែរ'],
                    ['en' => 'Paintings & Art', 'km' => 'គំនូរ'],
                    ['en' => 'Silk & Textiles', 'km' => 'សូត្រនិងវាយនភណ្ឌ'],
                    ['en' => 'Pottery & Ceramics', 'km' => 'គ្រឿងដីផ្កា'],
                    ['en' => 'Wood Carvings', 'km' => 'ចម្លាក់ឈើ'],
                    ['en' => 'Art Supplies', 'km' => 'សម្ភារៈសិល្បៈ'],
                ],
            ],
            [
                'en' => 'Building Materials', 'km' => 'សម្ភារៈសំណង់',
                'children' => [
                    ['en' => 'Cement & Concrete', 'km' => 'ស៊ីម៉ង់ត៍'],
                    ['en' => 'Paint', 'km' => 'ថ្នាំលាប'],
                    ['en' => 'Plumbing', 'km' => 'ប្រព័ន្ធទឹក'],
                    ['en' => 'Electrical', 'km' => 'គ្រឿងអគ្គិសនី'],
                    ['en' => 'Tiles & Flooring', 'km' => 'ក្បាល់និងឥដ្ឋ'],
                    ['en' => 'Doors & Windows', 'km' => 'ទ្វារនិងបង្អួច'],
                    ['en' => 'Roofing', 'km' => 'ដំបូល'],
                ],
            ],
            [
                'en' => 'Tools & Hardware', 'km' => 'ឧបករណ៍',
                'children' => [
                    ['en' => 'Hand Tools', 'km' => 'ឧបករណ៍ដៃ'],
                    ['en' => 'Power Tools', 'km' => 'ឧបករណ៍អគ្គិសនី'],
                    ['en' => 'Measuring Tools', 'km' => 'ឧបករណ៍វាស់'],
                    ['en' => 'Safety Equipment', 'km' => 'សម្ភារៈសុវត្ថិភាព'],
                    ['en' => 'Garden Tools', 'km' => 'ឧបករណ៍សួន'],
                ],
            ],
            [
                'en' => 'Home Supplies', 'km' => 'សម្ភារៈប្រើប្រាស់ក្នុងផ្ទះ',
                'children' => [
                    ['en' => 'Laundry', 'km' => 'បោកអ៊ុត'],
                    ['en' => 'Pest Control', 'km' => 'ការពារសត្វល្អិត'],
                    ['en' => 'Air Fresheners', 'km' => 'ទឹកអប់បន្ទប់'],
                    ['en' => 'Paper Products', 'km' => 'ផលិតផលក្រដាស'],
                ],
            ],
            [
                'en' => 'Pet Supplies', 'km' => 'សម្ភារៈសត្វចិញ្ចឹម',
                'children' => [
                    ['en' => 'Dog Supplies', 'km' => 'សម្ភារៈឆ្កែ'],
                    ['en' => 'Cat Supplies', 'km' => 'សម្ភារៈឆ្មា'],
                    ['en' => 'Fish & Aquarium', 'km' => 'ត្រីនិងអាងត្រី'],
                    ['en' => 'Pet Food', 'km' => 'អាហារសត្វ'],
                    ['en' => 'Pet Accessories', 'km' => 'គ្រឿងបន្ថែមសត្វ'],
                ],
            ],
            [
                'en' => 'Agriculture & Plants', 'km' => 'កសិកម្មនិងរុក្ខជាតិ',
                'children' => [
                    ['en' => 'Seeds', 'km' => 'គ្រាប់ពូជ'],
                    ['en' => 'Fertilizers', 'km' => 'ជី'],
                    ['en' => 'Garden Plants', 'km' => 'រុក្ខជាតិសួន'],
                    ['en' => 'Farming Equipment', 'km' => 'ឧបករណ៍កសិកម្ម'],
                    ['en' => 'Indoor Plants', 'km' => 'រុក្ខជាតិក្នុងផ្ទះ'],
                ],
            ],
            [
                'en' => 'Musical Instruments', 'km' => 'ឧបករណ៍តន្ត្រី',
                'children' => [
                    ['en' => 'Guitars', 'km' => 'ហ្គីតា'],
                    ['en' => 'Keyboards & Pianos', 'km' => 'ក្តារចុចនិងព្យាណូ'],
                    ['en' => 'Drums & Percussion', 'km' => 'ស្គរ'],
                    ['en' => 'Traditional Khmer Instruments', 'km' => 'ឧបករណ៍តន្ត្រីខ្មែរ'],
                    ['en' => 'Music Accessories', 'km' => 'គ្រឿងបន្ថែមតន្ត្រី'],
                ],
            ],
            [
                'en' => 'Books & Stationery', 'km' => 'សៀវភៅនិងសម្ភារៈការិយាល័យ',
                'children' => [
                    ['en' => 'Khmer Books', 'km' => 'សៀវភៅខ្មែរ'],
                    ['en' => 'English Books', 'km' => 'សៀវភៅអង់គ្លេស'],
                    ['en' => 'Notebooks & Pens', 'km' => 'សៀវភៅកត់ត្រានិងប៊ិក'],
                    ['en' => 'Office Supplies', 'km' => 'សម្ភារៈការិយាល័យ'],
                ],
            ],
            [
                'en' => 'Services', 'km' => 'សេវាកម្ម',
                'children' => [
                    ['en' => 'Delivery Services', 'km' => 'សេវាដឹកជញ្ជូន'],
                    ['en' => 'Repair Services', 'km' => 'សេវាជួសជុល'],
                    ['en' => 'Cleaning Services', 'km' => 'សេវាសម្អាត'],
                    ['en' => 'Digital Services', 'km' => 'សេវាឌីជីថល'],
                ],
            ],
        ];
    }
}
