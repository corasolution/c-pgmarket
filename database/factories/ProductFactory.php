<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->words(3, true);

        return [
            'shop_id' => Shop::factory(),
            'category_id' => Category::factory(),
            'name_i18n' => ['en' => ucwords($name), 'km' => ucwords($name)],
            'description_i18n' => ['en' => $this->faker->sentence(), 'km' => ''],
            'slug' => Str::slug($name).'-'.$this->faker->unique()->randomNumber(4),
            'status' => 'active',
            'is_featured' => false,
        ];
    }
}
