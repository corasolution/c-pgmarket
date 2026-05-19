<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->word();

        return [
            'name_i18n' => ['en' => ucfirst($name), 'km' => ucfirst($name)],
            'slug' => Str::slug($name).'-'.$this->faker->unique()->randomNumber(4),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
