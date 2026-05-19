<?php

namespace Database\Factories;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayoutFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'amount_cents' => $this->faker->numberBetween(1000, 100000),
            'amount_currency' => 'USD',
            'status' => 'pending',
            'bank_name' => 'ABA Bank',
            'bank_account_number' => $this->faker->numerify('##########'),
            'bank_account_name' => $this->faker->name(),
        ];
    }
}
