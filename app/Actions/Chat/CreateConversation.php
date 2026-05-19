<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Models\Conversation;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final class CreateConversation
{
    public function __invoke(User $buyer, Shop $shop): Conversation
    {
        if (! in_array($buyer->role, ['buyer', 'vendor_owner', 'vendor_staff'], strict: true)) {
            throw new AuthorizationException('Only buyers and vendors may initiate a conversation.');
        }

        if ($buyer->shop_id === $shop->id) {
            throw new AuthorizationException('Vendors cannot start a conversation with their own shop.');
        }

        return Conversation::firstOrCreate(
            ['buyer_id' => $buyer->id, 'shop_id' => $shop->id],
            ['last_message_at' => now()],
        );
    }
}
