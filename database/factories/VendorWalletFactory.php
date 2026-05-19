<?php

namespace Database\Factories;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

class VendorWalletFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'pending_balance_cents' => 0,
            'pending_balance_currency' => 'USD',
            'available_balance_cents' => 0,
            'available_balance_currency' => 'USD',
            'lifetime_earned_cents' => 0,
        ];
    }
}
