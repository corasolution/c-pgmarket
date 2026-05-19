<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Events\Chat\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final class SendMessage
{
    public function __invoke(
        User $sender,
        Conversation $conversation,
        string $body,
    ): Message {
        if ($sender->id !== $conversation->buyer_id && $sender->shop_id !== $conversation->shop_id) {
            throw new AuthorizationException('You are not a participant of this conversation.');
        }

        $message = $conversation->messages()->create([
            'sender_id' => $sender->id,
            'body' => $body,
        ]);

        broadcast(new MessageSent($message))->toOthers();

        return $message;
    }
}
