<?php

declare(strict_types=1);

use App\Actions\Shop\ApproveShop;
use App\Actions\Shop\SuspendShop;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $vendor = User::factory()->create(['role' => 'vendor_owner']);
    $this->shop = Shop::factory()->create([
        'owner_id' => $vendor->id,
        'status'   => 'submitted',
    ]);
});

test('admin can approve a submitted shop', function (): void {
    app(ApproveShop::class)($this->shop, $this->admin);

    expect($this->shop->fresh()->status)->toBe('active');
});

test('non-admin cannot approve a shop', function (): void {
    $buyer = User::factory()->create(['role' => 'buyer']);

    expect(fn () => app(ApproveShop::class)($this->shop, $buyer))
        ->toThrow(AuthorizationException::class);
});

test('admin can suspend an active shop', function (): void {
    $this->shop->update(['status' => 'active']);

    app(SuspendShop::class)($this->shop, $this->admin, 'Policy violation');

    expect($this->shop->fresh()->status)->toBe('suspended');
});

test('non-admin cannot suspend a shop', function (): void {
    $buyer = User::factory()->create(['role' => 'buyer']);

    expect(fn () => app(SuspendShop::class)($this->shop, $buyer, 'Trying to suspend'))
        ->toThrow(AuthorizationException::class);
});
