<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\SubOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
final class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        return [
            'sub_order_id'             => SubOrder::factory(),
            'product_variant_id'       => ProductVariant::factory(),
            'product_name_snapshot'    => $this->faker->words(3, true),
            'variant_sku_snapshot'     => strtoupper($this->faker->bothify('SKU-####')),
            'image_snapshot'           => null,
            'options_snapshot'         => ['Color' => 'Black'],
            'quantity'                 => $this->faker->numberBetween(1, 5),
            'unit_price_cents'         => $this->faker->numberBetween(500, 50000),
            'unit_price_currency'      => 'USD',
        ];
    }
}
