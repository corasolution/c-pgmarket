<?php

declare(strict_types=1);

use App\Actions\Chat\SendMessage;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function (): void {
    $this->buyer = User::factory()->create(['role' => 'buyer']);
    $vendor = User::factory()->create(['role' => 'vendor_owner']);
    $this->shop = Shop::factory()->create(['owner_id' => $vendor->id]);

    $this->conversation = Conversation::factory()->create([
        'buyer_id' => $this->buyer->id,
        'shop_id'  => $this->shop->id,
    ]);
});

test('buyer can send a message in their conversation', function (): void {
    $message = app(SendMessage::class)(
        sender: $this->buyer,
        conversation: $this->conversation,
        body: 'Hello, is this available?',
    );

    expect($message)->toBeInstanceOf(Message::class)
        ->and($message->body)->toBe('Hello, is this available?')
        ->and($message->sender_id)->toBe($this->buyer->id);
});

test('message body is persisted in the database', function (): void {
    app(SendMessage::class)(
        sender: $this->buyer,
        conversation: $this->conversation,
        body: 'Test message body',
    );

    $this->assertDatabaseHas('messages', [
        'conversation_id' => $this->conversation->id,
        'sender_id'       => $this->buyer->id,
        'body'            => 'Test message body',
    ]);
});

test('unauthorized user cannot send message to unrelated conversation', function (): void {
    $stranger = User::factory()->create(['role' => 'buyer']);

    expect(fn () => app(SendMessage::class)(
        sender: $stranger,
        conversation: $this->conversation,
        body: 'I should not be able to send this',
    ))->toThrow(AuthorizationException::class);
});
