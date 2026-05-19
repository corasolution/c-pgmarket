<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class AdditionalCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['en' => 'Health & Wellness',       'km' => 'សុខភាព និងសុខដុម'],
            ['en' => 'Mobile & Accessories',    'km' => 'ទូរស័ព្ទ និងគ្រឿងបន្លាស់'],
            ['en' => 'Computers & Laptops',     'km' => 'កុំព្យូទ័រ និងឡាប់ថប'],
            ['en' => 'Cameras & Photography',   'km' => 'កាមេរ៉ា និងថតរូប'],
            ['en' => 'Books & Media',           'km' => 'សៀវភៅ និងមេឌា'],
            ['en' => 'Games & Gaming',          'km' => 'ហ្គេម'],
            ['en' => 'Kitchen & Dining',        'km' => 'ផ្ទះបាយ និងបន្ទប់ទទួលទាន'],
            ['en' => 'Outdoor & Garden',        'km' => 'ខាងក្រៅ និងសួនច្បារ'],
            ['en' => 'Travel & Luggage',        'km' => 'ធ្វើដំណើរ និងកាបូប'],
            ['en' => 'Office Supplies',         'km' => 'គ្រឿងប្រើការិយាល័យ'],
            ['en' => 'Wedding & Events',        'km' => 'អាពាហ៍ពិពាហ៍ និងព្រឹត្តិការណ៍'],
            ['en' => 'Traditional Khmer',       'km' => 'ផលិតផលប្រពៃណីខ្មែរ'],
            ['en' => 'Fabric & Sewing',         'km' => 'ក្រណាត់ និងដេរ'],
            ['en' => 'Automotive',              'km' => 'យានយន្ត'],
            ['en' => 'Baby & Maternity',        'km' => 'ទារក និងមាតា'],
        ];

        $existingSlugs = Category::pluck('slug')->toArray();
        $maxOrder = Category::max('sort_order') ?? 0;

        foreach ($categories as $i => $c) {
            $slug = Str::slug($c['en']);
            if (in_array($slug, $existingSlugs, true)) {
                continue;
            }
            Category::create([
                'name_i18n'  => $c,
                'slug'       => $slug,
                'sort_order' => $maxOrder + $i + 1,
                'is_active'  => true,
            ]);
        }
    }
}
