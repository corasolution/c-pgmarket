<?php

declare(strict_types=1);

use App\Actions\Shop\SubmitShopForReview;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function (): void {
    $this->vendor = User::factory()->create(['role' => 'vendor_owner']);
    $this->shop = Shop::factory()->create([
        'owner_id' => $this->vendor->id,
        'status'   => 'draft',
    ]);
});

test('vendor owner can submit their draft shop for review', function (): void {
    app(SubmitShopForReview::class)($this->shop, $this->vendor);

    expect($this->shop->fresh()->status)->toBe('submitted');
});

test('different user cannot submit someone else shop', function (): void {
    $stranger = User::factory()->create(['role' => 'vendor_owner']);
    $strangerShop = Shop::factory()->create(['owner_id' => $stranger->id, 'status' => 'draft']);

    expect(fn () => app(SubmitShopForReview::class)($strangerShop, $this->vendor))
        ->toThrow(AuthorizationException::class);
});

test('cannot submit a shop that is not in draft status', function (): void {
    $this->shop->update(['status' => 'submitted']);

    expect(fn () => app(SubmitShopForReview::class)($this->shop, $this->vendor))
        ->toThrow(\RuntimeException::class);
});

test('cannot submit an already-active shop', function (): void {
    $this->shop->update(['status' => 'active']);

    expect(fn () => app(SubmitShopForReview::class)($this->shop, $this->vendor))
        ->toThrow(\RuntimeException::class);
});
