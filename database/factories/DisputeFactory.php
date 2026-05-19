<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Dispute;
use App\Models\OrderItem;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dispute>
 */
final class DisputeFactory extends Factory
{
    protected $model = Dispute::class;

    public function definition(): array
    {
        $orderItem = OrderItem::factory()->create();

        return [
            'order_item_id' => $orderItem->id,
            'buyer_id'      => User::factory()->state(['role' => 'buyer']),
            'shop_id'       => Shop::factory(),
            'reason'        => $this->faker->randomElement(['item_not_received', 'item_not_as_described', 'damaged']),
            'description'   => $this->faker->sentence(),
            'status'        => 'open',
        ];
    }
}
