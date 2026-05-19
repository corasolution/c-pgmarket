<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
final class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory(),
            'buyer_id'      => User::factory()->state(['role' => 'buyer']),
            'product_id'    => Product::factory(),
            'shop_id'       => Shop::factory(),
            'rating'        => $this->faker->numberBetween(1, 5),
            'body'          => $this->faker->optional()->sentence(),
            'is_verified'   => true,
        ];
    }
}
