<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Shipment;
use App\Models\SubOrder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Shipment>
 */
final class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    public function definition(): array
    {
        return [
            'sub_order_id'           => SubOrder::factory(),
            'provider'               => 'stub',
            'tracking_number'        => 'TRACK-'.strtoupper(Str::random(8)),
            'status'                 => 'pending',
            'shipping_fee_cents'     => $this->faker->numberBetween(0, 5000),
            'shipping_fee_currency'  => 'USD',
            'estimated_delivery_at'  => now()->addDays(3),
        ];
    }
}
