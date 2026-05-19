<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Payment>
 */
final class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'provider' => 'aba_payway',
            'transaction_id' => strtoupper($this->faker->bothify('TXN-########')),
            'status' => 'pending',
            'amount_cents' => $this->faker->numberBetween(1000, 100000),
            'amount_currency' => 'USD',
        ];
    }
}
