<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shop;
use App\Models\SubOrder;
use App\Models\User;
use App\Models\VendorWallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class DemoOrderSeeder extends Seeder
{
    public function run(): void
    {
        $buyer = User::where('email', 'buyer@corasoft.com')->firstOrFail();
        $shops = Shop::where('status', 'active')->with('owner')->get();

        if ($shops->isEmpty()) {
            $this->command->warn('No active shops found.');
            return;
        }

        $statuses = ['pending', 'paid', 'accepted', 'packed', 'in_transit', 'delivered', 'completed'];

        foreach (range(1, 10) as $i) {
            // Pick 1-3 random shops for this order (multi-vendor cart)
            $orderShops = $shops->random(min(rand(1, 3), $shops->count()));
            $totalCents = 0;
            $subOrdersData = [];

            foreach ($orderShops as $shop) {
                $products = Product::withoutGlobalScopes()
                    ->where('shop_id', $shop->id)
                    ->where('status', 'active')
                    ->with('variants')
                    ->inRandomOrder()
                    ->take(rand(1, 3))
                    ->get();

                if ($products->isEmpty()) {
                    continue;
                }

                $shopItems = [];
                $shopSubtotal = 0;

                foreach ($products as $product) {
                    $variant = $product->variants->first();
                    if (! $variant) {
                        continue;
                    }

                    $qty = rand(1, 3);
                    $lineTotal = $variant->price_cents * $qty;
                    $shopSubtotal += $lineTotal;

                    $shopItems[] = [
                        'variant' => $variant,
                        'product' => $product,
                        'qty' => $qty,
                    ];
                }

                if (empty($shopItems)) {
                    continue;
                }

                $totalCents += $shopSubtotal;
                $subOrdersData[] = [
                    'shop' => $shop,
                    'items' => $shopItems,
                    'subtotal' => $shopSubtotal,
                    'currency' => $shopItems[0]['variant']->price_currency,
                ];
            }

            if (empty($subOrdersData)) {
                continue;
            }

            $status = $statuses[array_rand($statuses)];
            $createdAt = now()->subDays(rand(1, 90))->subHours(rand(0, 23));

            $order = Order::create([
                'reference' => 'ORD-' . strtoupper(Str::random(8)),
                'buyer_id' => $buyer->id,
                'status' => $status,
                'total_cents' => $totalCents,
                'total_currency' => 'USD',
                'shipping_address' => [
                    'name' => $buyer->name,
                    'phone' => '+855 12 345 678',
                    'address_line' => '123 Norodom Blvd, Sangkat Tonle Bassac',
                    'city' => 'Phnom Penh',
                    'province' => 'Phnom Penh',
                ],
                'note' => rand(0, 3) === 0 ? 'Please deliver before 5pm' : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            // Create payment for paid+ orders
            $isPaid = in_array($status, ['paid', 'accepted', 'packed', 'in_transit', 'delivered', 'completed'], true);
            if ($isPaid) {
                Payment::create([
                    'order_id' => $order->id,
                    'provider' => 'aba_payway',
                    'transaction_id' => $order->reference,
                    'status' => 'paid',
                    'amount_cents' => $totalCents,
                    'amount_currency' => 'USD',
                    'paid_at' => $createdAt->addMinutes(rand(2, 10)),
                ]);
            }

            foreach ($subOrdersData as $soData) {
                $subOrder = SubOrder::create([
                    'order_id' => $order->id,
                    'shop_id' => $soData['shop']->id,
                    'status' => $status,
                    'subtotal_cents' => $soData['subtotal'],
                    'subtotal_currency' => $soData['currency'],
                    'shipping_fee_cents' => rand(0, 1) === 0 ? 0 : rand(200, 500),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                foreach ($soData['items'] as $itemData) {
                    OrderItem::create([
                        'sub_order_id' => $subOrder->id,
                        'product_variant_id' => $itemData['variant']->id,
                        'product_name_snapshot' => $itemData['product']->name_i18n['en'] ?? 'Product',
                        'variant_sku_snapshot' => $itemData['variant']->sku,
                        'image_snapshot' => $itemData['product']->images[0] ?? null,
                        'options_snapshot' => $itemData['variant']->options ?? [],
                        'quantity' => $itemData['qty'],
                        'unit_price_cents' => $itemData['variant']->price_cents,
                        'unit_price_currency' => $itemData['variant']->price_currency,
                    ]);
                }

                // Credit vendor wallet for paid orders
                if ($isPaid) {
                    $wallet = VendorWallet::firstOrCreate(
                        ['shop_id' => $soData['shop']->id],
                        [
                            'pending_balance_cents' => 0,
                            'pending_balance_currency' => 'USD',
                            'available_balance_cents' => 0,
                            'available_balance_currency' => 'USD',
                            'lifetime_earned_cents' => 0,
                        ],
                    );

                    $isReleased = in_array($status, ['delivered', 'completed'], true);
                    $commission = (int) round($soData['subtotal'] * 0.08);
                    $net = $soData['subtotal'] - $commission;

                    if ($isReleased) {
                        $wallet->increment('available_balance_cents', $net);
                    } else {
                        $wallet->increment('pending_balance_cents', $soData['subtotal']);
                    }
                    $wallet->increment('lifetime_earned_cents', $soData['subtotal']);

                    WalletTransaction::create([
                        'vendor_wallet_id' => $wallet->id,
                        'sub_order_id' => $subOrder->id,
                        'type' => 'credit',
                        'reason' => $isReleased ? 'escrow_release' : 'order_payment',
                        'amount_cents' => $isReleased ? $net : $soData['subtotal'],
                        'amount_currency' => 'USD',
                        'balance_after_cents' => $isReleased
                            ? $wallet->available_balance_cents
                            : $wallet->pending_balance_cents,
                        'reference' => ($isReleased ? 'ESC-' : 'PAY-') . $subOrder->id,
                        'note' => $isReleased ? "Commission 8%: -\${$commission}" : null,
                    ]);
                }
            }

            $this->command->info("Order #{$order->reference} — {$status} — " . count($subOrdersData) . " shop(s) — \$" . number_format($totalCents / 100, 2));
        }

        $this->command->info('Demo orders seeded successfully.');
    }
}
