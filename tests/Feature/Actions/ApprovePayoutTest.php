<?php

declare(strict_types=1);

use App\Actions\Payout\ApprovePayout;
use App\Models\Payout;
use App\Models\Shop;
use App\Models\User;
use App\Models\VendorWallet;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $vendor = User::factory()->create(['role' => 'vendor_owner']);
    $this->shop = Shop::factory()->create(['owner_id' => $vendor->id]);

    $this->wallet = VendorWallet::factory()->create([
        'shop_id' => $this->shop->id,
        'available_balance_cents' => 50000,
        'pending_balance_cents' => 0,
        'lifetime_earned_cents' => 50000,
    ]);

    $this->payout = Payout::factory()->create([
        'shop_id' => $this->shop->id,
        'amount_cents' => 20000,
        'status' => 'pending',
    ]);
});

test('admin can approve a payout and wallet is debited', function (): void {
    app(ApprovePayout::class)($this->admin, $this->payout);

    expect($this->payout->fresh()->status)->toBe('approved')
        ->and($this->wallet->fresh()->available_balance_cents)->toBe(30000);
});

test('non-admin cannot approve a payout', function (): void {
    $buyer = User::factory()->create(['role' => 'buyer']);

    expect(fn () => app(ApprovePayout::class)($buyer, $this->payout))
        ->toThrow(AuthorizationException::class);
});

test('cannot approve an already-approved payout', function (): void {
    $this->payout->update(['status' => 'approved']);

    expect(fn () => app(ApprovePayout::class)($this->admin, $this->payout))
        ->toThrow(\DomainException::class);
});

test('cannot approve a rejected payout', function (): void {
    $this->payout->update(['status' => 'rejected']);

    expect(fn () => app(ApprovePayout::class)($this->admin, $this->payout))
        ->toThrow(\DomainException::class);
});
