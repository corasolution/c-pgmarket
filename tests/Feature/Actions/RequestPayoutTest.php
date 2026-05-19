<?php

declare(strict_types=1);

use App\Actions\Payout\RequestPayout;
use App\Models\Payout;
use App\Models\Shop;
use App\Models\User;
use App\Models\VendorWallet;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function (): void {
    $this->vendor = User::factory()->create(['role' => 'vendor_owner']);
    $this->shop = Shop::factory()->create(['owner_id' => $this->vendor->id]);

    VendorWallet::factory()->create([
        'shop_id'                    => $this->shop->id,
        'available_balance_cents'    => 30000,
        'available_balance_currency' => 'USD',
        'pending_balance_cents'      => 0,
        'pending_balance_currency'   => 'USD',
        'lifetime_earned_cents'      => 30000,
    ]);

    $this->vendor->update(['shop_id' => $this->shop->id]);
});

test('vendor owner can request a payout within available balance', function (): void {
    $payout = app(RequestPayout::class)(
        user: $this->vendor,
        shop: $this->shop,
        amountCents: 20000,
        bankName: 'ABA Bank',
        bankAccountNumber: '001234567',
        bankAccountName: 'Test Vendor',
    );

    expect($payout)->toBeInstanceOf(Payout::class)
        ->and($payout->amount_cents)->toBe(20000)
        ->and($payout->status)->toBe('pending')
        ->and($payout->bank_name)->toBe('ABA Bank');
});

test('payout request fails if amount exceeds available balance', function (): void {
    expect(fn () => app(RequestPayout::class)(
        user: $this->vendor,
        shop: $this->shop,
        amountCents: 99999,
        bankName: 'ABA Bank',
        bankAccountNumber: '001234567',
        bankAccountName: 'Test Vendor',
    ))->toThrow(\DomainException::class);
});

test('buyer cannot request a payout', function (): void {
    $buyer = User::factory()->create(['role' => 'buyer']);
    $buyerShop = Shop::factory()->create(['owner_id' => $buyer->id]);

    expect(fn () => app(RequestPayout::class)(
        user: $buyer,
        shop: $buyerShop,
        amountCents: 1000,
        bankName: 'ABA Bank',
        bankAccountNumber: '001234567',
        bankAccountName: 'Buyer Name',
    ))->toThrow(AuthorizationException::class);
});

test('vendor cannot request payout for a shop they do not own', function (): void {
    $otherVendor = User::factory()->create(['role' => 'vendor_owner']);
    $otherShop = Shop::factory()->create(['owner_id' => $otherVendor->id]);

    expect(fn () => app(RequestPayout::class)(
        user: $this->vendor,
        shop: $otherShop,
        amountCents: 1000,
        bankName: 'ABA Bank',
        bankAccountNumber: '001234567',
        bankAccountName: 'Test Vendor',
    ))->toThrow(AuthorizationException::class);
});
