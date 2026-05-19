<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
final class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'buyer_id'        => User::factory()->state(['role' => 'buyer']),
            'shop_id'         => Shop::factory(),
            'last_message_at' => now(),
        ];
    }
}
