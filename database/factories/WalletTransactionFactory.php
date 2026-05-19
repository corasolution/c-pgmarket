<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\VendorWallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WalletTransaction>
 */
final class WalletTransactionFactory extends Factory
{
    protected $model = WalletTransaction::class;

    public function definition(): array
    {
        return [
            'vendor_wallet_id'   => VendorWallet::factory(),
            'sub_order_id'       => null,
            'type'               => $this->faker->randomElement(['credit', 'debit', 'hold', 'release']),
            'reason'             => 'escrow_release',
            'amount_cents'       => $this->faker->numberBetween(100, 50000),
            'amount_currency'    => 'USD',
            'balance_after_cents' => $this->faker->numberBetween(0, 100000),
            'reference'          => 'TXN-'.strtoupper($this->faker->bothify('####??')),
        ];
    }
}
