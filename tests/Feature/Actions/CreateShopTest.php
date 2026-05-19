<?php

declare(strict_types=1);

use App\Actions\Shop\CreateShop;
use App\Models\Shop;
use App\Models\User;
use App\Models\VendorWallet;
use Illuminate\Auth\Access\AuthorizationException;

test('vendor owner can create a shop and wallet is provisioned', function (): void {
    $vendor = User::factory()->create(['role' => 'vendor_owner']);

    $shop = app(CreateShop::class)($vendor, [
        'name'     => 'Test Shop KH',
        'currency' => 'USD',
    ]);

    expect($shop)->toBeInstanceOf(Shop::class)
        ->and($shop->owner_id)->toBe($vendor->id)
        ->and($shop->status)->toBe('draft')
        ->and($shop->name)->toBe('Test Shop KH');

    expect(VendorWallet::where('shop_id', $shop->id)->exists())->toBeTrue();
});

test('vendor is linked to shop after creation', function (): void {
    $vendor = User::factory()->create(['role' => 'vendor_owner']);

    $shop = app(CreateShop::class)($vendor, ['name' => 'Link Test Shop']);

    expect($vendor->fresh()->shop_id)->toBe($shop->id);
});

test('buyer cannot create a shop', function (): void {
    $buyer = User::factory()->create(['role' => 'buyer']);

    expect(fn () => app(CreateShop::class)($buyer, ['name' => 'Buyer Shop']))
        ->toThrow(AuthorizationException::class);
});

test('vendor cannot create a second shop', function (): void {
    $vendor = User::factory()->create(['role' => 'vendor_owner']);
    app(CreateShop::class)($vendor, ['name' => 'First Shop']);

    expect(fn () => app(CreateShop::class)($vendor, ['name' => 'Second Shop']))
        ->toThrow(\RuntimeException::class);
});

test('shop slug is auto-generated from name', function (): void {
    $vendor = User::factory()->create(['role' => 'vendor_owner']);

    $shop = app(CreateShop::class)($vendor, ['name' => 'Phnom Penh Gadgets']);

    expect($shop->slug)->toStartWith('phnom-penh-gadgets');
});
