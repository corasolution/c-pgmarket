<?php

declare(strict_types=1);

use App\Actions\Payment\ReleaseEscrow;
use App\Models\Order;
use App\Models\Shop;
use App\Models\SubOrder;
use App\Models\User;
use App\Models\VendorWallet;
use App\Models\WalletTransaction;

beforeEach(function (): void {
    $vendor = User::factory()->create(['role' => 'vendor_owner']);
    $this->shop = Shop::factory()->create([
        'owner_id'           => $vendor->id,
        'commission_percent' => 8,
    ]);

    $this->wallet = VendorWallet::factory()->create([
        'shop_id'                    => $this->shop->id,
        'available_balance_cents'    => 0,
        'available_balance_currency' => 'USD',
        'pending_balance_cents'      => 10000,
        'pending_balance_currency'   => 'USD',
        'lifetime_earned_cents'      => 10000,
    ]);

    $buyer = User::factory()->create(['role' => 'buyer']);
    $order = Order::factory()->create(['buyer_id' => $buyer->id]);

    $this->subOrder = SubOrder::factory()->create([
        'order_id'          => $order->id,
        'shop_id'           => $this->shop->id,
        'status'            => 'delivered',
        'subtotal_cents'    => 10000,
        'subtotal_currency' => 'USD',
    ]);
});

test('escrow release moves pending balance to available minus commission', function (): void {
    app(ReleaseEscrow::class)($this->subOrder);

    $wallet = $this->wallet->fresh();

    expect($wallet->pending_balance_cents)->toBe(0)
        ->and($wallet->available_balance_cents)->toBe(9200);
});

test('escrow release creates a wallet transaction record', function (): void {
    app(ReleaseEscrow::class)($this->subOrder);

    expect(WalletTransaction::where('vendor_wallet_id', $this->wallet->id)->count())->toBeGreaterThanOrEqual(1);
});

test('commission is deducted at the configured rate', function (): void {
    $this->shop->update(['commission_percent' => 10]);

    app(ReleaseEscrow::class)($this->subOrder);

    expect($this->wallet->fresh()->available_balance_cents)->toBe(9000);
});
