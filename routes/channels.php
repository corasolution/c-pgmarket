<?php

declare(strict_types=1);

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private chat channel: buyer and the shop's vendor may listen
Broadcast::channel('conversation.{conversationId}', function ($user, int $conversationId) {
    $conversation = Conversation::find($conversationId);

    if (! $conversation) {
        return false;
    }

    if ($conversation->buyer_id === $user->id) {
        return true;
    }

    if ($user->shop_id === $conversation->shop_id && $user->isVendor()) {
        return true;
    }

    return $user->isAdmin();
});
