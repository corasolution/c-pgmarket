<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\Shop;
use App\Models\SubOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubOrder>
 */
final class SubOrderFactory extends Factory
{
    protected $model = SubOrder::class;

    public function definition(): array
    {
        return [
            'order_id'           => Order::factory(),
            'shop_id'            => Shop::factory(),
            'status'             => 'pending',
            'subtotal_cents'     => $this->faker->numberBetween(1000, 100000),
            'subtotal_currency'  => 'USD',
            'shipping_fee_cents' => 0,
        ];
    }
}
