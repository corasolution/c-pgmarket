<?php

declare(strict_types=1);

use App\Actions\Chat\CreateConversation;
use App\Models\Conversation;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function (): void {
    $this->buyer = User::factory()->create(['role' => 'buyer']);
    $vendor = User::factory()->create(['role' => 'vendor_owner']);
    $this->shop = Shop::factory()->create(['owner_id' => $vendor->id]);
    $vendor->update(['shop_id' => $this->shop->id]);
});

test('buyer can start a conversation with a shop', function (): void {
    $conversation = app(CreateConversation::class)($this->buyer, $this->shop);

    expect($conversation)->toBeInstanceOf(Conversation::class)
        ->and($conversation->buyer_id)->toBe($this->buyer->id)
        ->and($conversation->shop_id)->toBe($this->shop->id);
});

test('repeated calls return the same conversation (idempotent)', function (): void {
    $first  = app(CreateConversation::class)($this->buyer, $this->shop);
    $second = app(CreateConversation::class)($this->buyer, $this->shop);

    expect($first->id)->toBe($second->id);
    expect(Conversation::count())->toBe(1);
});

test('vendor cannot start conversation with their own shop', function (): void {
    $vendor = $this->shop->owner;

    expect(fn () => app(CreateConversation::class)($vendor, $this->shop))
        ->toThrow(AuthorizationException::class);
});

test('conversation is persisted to the database', function (): void {
    app(CreateConversation::class)($this->buyer, $this->shop);

    $this->assertDatabaseHas('conversations', [
        'buyer_id' => $this->buyer->id,
        'shop_id'  => $this->shop->id,
    ]);
});
