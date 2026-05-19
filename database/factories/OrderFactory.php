<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Order>
 */
final class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'buyer_id' => User::factory(),
            'reference' => strtoupper($this->faker->bothify('ORD-####??')),
            'status' => 'pending',
            'total_cents' => $this->faker->numberBetween(1000, 100000),
            'total_currency' => 'USD',
            'shipping_address' => json_encode([
                'name' => $this->faker->name(),
                'phone' => $this->faker->phoneNumber(),
                'address' => $this->faker->streetAddress(),
                'city' => $this->faker->city(),
            ]),
        ];
    }
}
