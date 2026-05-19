# ABA PayWay Integration Guide

> Portable reference for integrating ABA PayWay (KHQR checkout + Payout to vendors) into a Laravel + Inertia.js + React project.
> Based on a production integration with lessons learned from ABA team reviews.

---

## Table of Contents

### Part 1 — Accept Payments (KHQR Checkout)
1. [Overview](#overview)
2. [Environment Setup](#environment-setup)
3. [Database Schema](#database-schema)
4. [Backend — AbaPayService](#backend--abapayservice)
5. [Backend — WebhookController](#backend--webhookcontroller)
6. [Backend — CheckoutController](#backend--checkoutcontroller)
7. [Backend — OrderController (Polling)](#backend--ordercontroller-polling)
8. [Frontend — Blade Template (PayWay JS SDK)](#frontend--blade-template-payway-js-sdk)
9. [Frontend — AbaPayCheckout.jsx](#frontend--abapaycheckoutjsx)
10. [Frontend — OrderConfirmation.jsx (Polling UI)](#frontend--orderconfirmationjsx-polling-ui)
11. [Route Registration](#route-registration)
12. [Mail & Events](#mail--events)
13. [Payment Flow (Step by Step)](#payment-flow-step-by-step)
14. [Polling Strategy](#polling-strategy)
15. [Gotchas & Lessons Learned](#gotchas--lessons-learned)

### Part 2 — Payout to Vendors
16. [Payout Overview](#payout-overview)
17. [Payout Environment Setup](#payout-environment-setup)
18. [RSA Encryption (Required for Payout)](#rsa-encryption-required-for-payout)
19. [Add Beneficiary (Whitelist Vendor)](#add-beneficiary-whitelist-vendor)
20. [Update Beneficiary Status](#update-beneficiary-status)
21. [Execute Payout](#execute-payout)
22. [Pre-Auth with Payout (Hold & Split)](#pre-auth-with-payout-hold--split)
23. [Get Transactions by Reference](#get-transactions-by-reference)
24. [Payout Laravel Service](#payout-laravel-service)
25. [Payout Status Codes](#payout-status-codes)
26. [Payout Flow (Step by Step)](#payout-flow-step-by-step)

---

## Overview

**ABA PayWay** is Cambodia's dominant payment gateway operated by ABA Bank. The **KHQR** option lets customers scan a QR code with any Cambodian banking app (not just ABA) to pay.

**Integration method**: PayWay **popup checkout** — a JavaScript popup opens on your page, renders the KHQR QR code, and handles the payment flow. Your server is notified via a **webhook (pushback)** when payment completes.

**Tech stack assumed**:
- Laravel 12+ (PHP 8.3+)
- Inertia.js v2 + React 18
- MySQL

---

## Environment Setup

### .env variables

```env
ABA_MERCHANT_ID=your_merchant_id
ABA_MERCHANT_NAME=YourStoreName
ABA_API_KEY=your_api_key_here
ABA_PAYWAY_URL=https://checkout-sandbox.payway.com.kh
```

> **Production**: Change `ABA_PAYWAY_URL` to `https://checkout.payway.com.kh`

### config/services.php

```php
'aba' => [
    'merchant_id'   => env('ABA_MERCHANT_ID', ''),
    'merchant_name' => env('ABA_MERCHANT_NAME', ''),
    'api_key'       => env('ABA_API_KEY', ''),
    'payway_url'    => env('ABA_PAYWAY_URL', 'https://checkout-sandbox.payway.com.kh'),
],
```

---

## Database Schema

### Orders table — ABA-relevant columns

```php
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->string('order_number')->unique();       // Used as tran_id for ABA
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->string('customer_name');
    $table->string('customer_email');
    $table->string('customer_phone');
    $table->string('province');
    $table->string('district')->nullable();
    $table->text('street_address');
    $table->string('house_number')->nullable();
    $table->text('delivery_notes')->nullable();
    $table->decimal('subtotal', 10, 2);
    $table->decimal('shipping', 10, 2)->default(0);
    $table->decimal('total', 10, 2);
    $table->string('payment_method');                // 'aba_pay' or 'cod'
    $table->string('payment_status')->default('pending'); // pending, paid, failed
    $table->string('order_status')->default('new');
    $table->string('aba_qr_string')->nullable();
    $table->string('aba_transaction_id')->nullable(); // ABA's reference from webhook
    $table->timestamp('paid_at')->nullable();
    $table->timestamps();
});
```

### Order model — key fields

```php
protected $fillable = [
    'order_number', 'user_id', 'customer_name', 'customer_email', 'customer_phone',
    'province', 'district', 'street_address', 'house_number', 'delivery_notes',
    'subtotal', 'shipping', 'total', 'payment_method', 'payment_status',
    'order_status', 'aba_qr_string', 'aba_transaction_id', 'paid_at',
];

protected $casts = [
    'subtotal' => 'decimal:2',
    'shipping' => 'decimal:2',
    'total'    => 'decimal:2',
    'paid_at'  => 'datetime',
];

public static function generateOrderNumber(): string
{
    return 'MYSTORE-' . strtoupper(substr(uniqid(), -6));
}
```

---

## Backend — AbaPayService

`app/Services/AbaPayService.php`

This is the core service. Three public methods:
- `getPaymentFormData()` — builds signed form fields for the popup
- `checkPaymentStatus()` — calls check-transaction-2 API
- `verifyWebhookSignature()` — validates incoming webhook

```php
<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AbaPayService
{
    private string $merchantId;
    private string $apiKey;
    private string $paywayUrl;

    public function __construct()
    {
        $this->merchantId = config('services.aba.merchant_id', '');
        $this->apiKey     = config('services.aba.api_key', '');
        $this->paywayUrl  = rtrim(config('services.aba.payway_url', 'https://checkout-sandbox.payway.com.kh'), '/');
    }

    /**
     * Build all signed form fields for the PayWay popup checkout.
     * Returns an array ready to be rendered as hidden inputs in the browser.
     * No HTTP call is made — the browser submits directly to PayWay via the JS popup.
     */
    public function getPaymentFormData(Order $order): array
    {
        $reqTime  = now()->utc()->format('YmdHis');
        $tranId   = $order->order_number;
        $amount   = number_format((float) $order->total, 2, '.', '');
        $shipping = number_format((float) $order->shipping, 2, '.', '');

        $items = $order->items->map(fn($i) => [
            'name'     => $i->product_name,
            'quantity' => (int) $i->quantity,
            'price'    => number_format((float) $i->unit_price, 2, '.', ''),
        ])->values()->toJson();

        $nameParts = explode(' ', trim($order->customer_name), 2);
        $firstname = $nameParts[0] ?? $order->customer_name;
        $lastname  = $nameParts[1] ?? '';
        $phone     = $this->normalizePhone($order->customer_phone);

        $confirmationUrl    = route('orders.confirmation', $order->order_number);
        // ABA PayWay requires return_url to be Base64-encoded
        $returnUrl          = base64_encode($confirmationUrl);
        $cancelUrl          = route('cart.index');
        $continueSuccessUrl = route('home');
        $returnDeeplink     = '';
        $currency           = 'USD';
        $customFields       = '';
        $returnParams       = '';
        $lifetime           = '15';           // minutes — matches our max polling duration
        $additionalParams   = '';
        $googlePayToken     = '';
        $skipSuccessPage    = '0';
        $type               = 'purchase';
        $paymentOption      = 'abapay_khqr';  // MUST be sent — omitting shows generic chooser

        // Hash fields must be concatenated in this exact order (empty strings included)
        $hashData = implode('', [
            $reqTime, $this->merchantId, $tranId, $amount, $items, $shipping,
            $firstname, $lastname, $order->customer_email, $phone,
            $type, $paymentOption,
            $returnUrl, $cancelUrl, $continueSuccessUrl, $returnDeeplink,
            $currency, $customFields, $returnParams,
            $lifetime, $additionalParams, $googlePayToken, $skipSuccessPage,
        ]);

        // Filter out empty/null values — ABA rejects them with "Please remove null params"
        return array_filter([
            'action'               => "{$this->paywayUrl}/api/payment-gateway/v1/payments/purchase",
            'req_time'             => $reqTime,
            'merchant_id'          => $this->merchantId,
            'tran_id'              => $tranId,
            'amount'               => $amount,
            'items'                => $items,
            'shipping'             => $shipping,
            'firstname'            => $firstname,
            'lastname'             => $lastname,
            'email'                => $order->customer_email,
            'phone'                => $phone,
            'type'                 => $type,
            'payment_option'       => $paymentOption,
            'return_url'           => $returnUrl,
            'cancel_url'           => $cancelUrl,
            'continue_success_url' => $continueSuccessUrl,
            'return_deeplink'      => $returnDeeplink,
            'currency'             => $currency,
            'custom_fields'        => $customFields,
            'return_params'        => $returnParams,
            'lifetime'             => $lifetime,
            'additional_params'    => $additionalParams,
            'google_pay_token'     => $googlePayToken,
            'skip_success_page'    => $skipSuccessPage,
            'hash'                 => $this->sign($hashData),
        ], fn($v) => $v !== '' && $v !== null);
    }

    /**
     * Call PayWay check-transaction-2 and return the status result.
     */
    public function checkPaymentStatus(string $tranId): array
    {
        $reqTime  = now()->utc()->format('YmdHis');
        $hashData = $reqTime . $this->merchantId . $tranId;
        $hash     = $this->sign($hashData);

        try {
            $response = Http::timeout(15)
                ->asForm()
                ->post("{$this->paywayUrl}/api/payment-gateway/v1/payments/check-transaction-2", [
                    'req_time'    => $reqTime,
                    'merchant_id' => $this->merchantId,
                    'tran_id'     => $tranId,
                    'hash'        => $hash,
                ]);

            $data = $response->json();

            if (isset($data['status']['code']) && $data['status']['code'] === '00') {
                $tranStatus = $data['data']['tran_status'] ?? '';
                return [
                    'paid'   => $tranStatus === 'SUCCESS',
                    'status' => strtolower($tranStatus),
                    'raw'    => $data,
                ];
            }
        } catch (RequestException $e) {
            Log::error('ABA PayWay check-transaction error', [
                'message' => $e->getMessage(),
                'tran_id' => $tranId,
            ]);
        }

        return ['paid' => false, 'status' => 'unknown'];
    }

    /**
     * Verify the webhook signature from a PayWay pushback callback.
     * Fields are sorted alphabetically by key, values concatenated, then HMAC-SHA512.
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->input('hash')
            ?? $request->header('X_PAYWAY_HMAC_SHA512')
            ?? $request->header('X-PAYWAY-HMAC-SHA512');

        if (!$signature) {
            return false;
        }

        $postData = $request->except('hash');
        ksort($postData);
        $hashData = implode('', array_values($postData));
        $expected = $this->sign($hashData);

        return hash_equals($expected, $signature);
    }

    /**
     * Normalize a Cambodian phone number to PayWay's required format: 855XXXXXXXXX
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (str_starts_with($digits, '855')) {
            return $digits;
        }
        if (str_starts_with($digits, '0')) {
            return '855' . substr($digits, 1);
        }
        return '855' . $digits;
    }

    /**
     * HMAC-SHA512 signature, Base64-encoded.
     */
    private function sign(string $data): string
    {
        return base64_encode(hash_hmac('sha512', $data, $this->apiKey, true));
    }
}
```

---

## Backend — WebhookController

`app/Http/Controllers/WebhookController.php`

**Critical rules:**
- ALWAYS return plain-text `200 "Completed"` — ABA rejects HTML error pages
- Wrap everything in try-catch — any exception must still return `"Completed"`
- Re-confirm payment via Check Transaction API before marking paid
- Do NOT `sleep()` — ABA will timeout the pushback connection

```php
<?php

namespace App\Http\Controllers;

use App\Events\OrderPaid;
use App\Models\Order;
use App\Services\AbaPayService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function abaPay(Request $request, AbaPayService $abaPayService): Response
    {
        try {
            if (!$abaPayService->verifyWebhookSignature($request)) {
                Log::warning('ABA PayWay webhook: invalid signature', [
                    'ip'     => $request->ip(),
                    'fields' => $request->except('hash'),
                ]);
                return response('Completed', 200)->header('Content-Type', 'text/plain');
            }

            $transactionId = $request->input('tran_id');
            $abaTranId     = $request->input('aba_tran');
            $pushedStatus  = $request->input('status');

            $reconfirmed = false;
            if ($transactionId) {
                $check = $abaPayService->checkPaymentStatus($transactionId);
                $reconfirmed = $check['paid'] ?? false;
            }

            if (($pushedStatus === 'SUCCESS' || $reconfirmed) && $transactionId) {
                $order = Order::where('order_number', $transactionId)->first();

                if ($order && $order->payment_status !== 'paid') {
                    $order->update([
                        'payment_status'     => 'paid',
                        'order_status'       => 'confirmed',
                        'aba_transaction_id' => $abaTranId ?? $transactionId,
                        'paid_at'            => now(),
                    ]);

                    event(new OrderPaid($order));
                }
            }
        } catch (\Throwable $e) {
            Log::error('ABA PayWay webhook: unhandled exception', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }

        // Always return plain-text 200 — ABA rejects HTML error pages
        return response('Completed', 200)->header('Content-Type', 'text/plain');
    }
}
```

---

## Backend — CheckoutController

`app/Http/Controllers/Shop/CheckoutController.php`

The `store()` method creates the order and initiates ABA payment:

```php
<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\AbaPayService;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function index(): Response|RedirectResponse
    {
        if (CartService::isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        return Inertia::render('Shop/Checkout', [
            'cartItems' => CartService::getItems(),
            'cartTotal' => CartService::getTotal(),
        ]);
    }

    public function store(Request $request): Response|RedirectResponse
    {
        $validated = $request->validate([
            'full_name'      => 'required|string|max:255',
            'email'          => 'required|email|max:255',
            'phone'          => 'required|string|max:20',
            'province'       => 'required|string|max:100',
            'district'       => 'nullable|string|max:100',
            'street_address' => 'required|string|max:500',
            'house_number'   => 'nullable|string|max:50',
            'delivery_notes' => 'nullable|string|max:500',
            'payment_method' => 'required|in:aba_pay,cod',
        ]);

        if (CartService::isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $items    = CartService::getItems();
        $subtotal = CartService::getTotal();
        $shipping = $subtotal >= 500 ? 0 : 10;
        $total    = $subtotal + $shipping;

        $order = Order::create([
            'order_number'   => Order::generateOrderNumber(),
            'user_id'        => auth()->id(),
            'customer_name'  => $validated['full_name'],
            'customer_email' => $validated['email'],
            'customer_phone' => $validated['phone'],
            'province'       => $validated['province'],
            'district'       => $validated['district'] ?? null,
            'street_address' => $validated['street_address'],
            'house_number'   => $validated['house_number'] ?? null,
            'delivery_notes' => $validated['delivery_notes'] ?? null,
            'subtotal'       => $subtotal,
            'shipping'       => $shipping,
            'total'          => $total,
            'payment_method' => $validated['payment_method'],
            'payment_status' => 'pending',
            'order_status'   => 'new',
        ]);

        foreach ($items as $item) {
            OrderItem::create([
                'order_id'     => $order->id,
                'product_id'   => $item['product_id'],
                'product_name' => $item['name'],
                'product_sku'  => $item['sku'],
                'unit_price'   => $item['price'],
                'quantity'     => $item['quantity'],
                'subtotal'     => $item['price'] * $item['quantity'],
            ]);
        }

        CartService::clear();
        Mail::to($order->customer_email)->queue(new OrderConfirmationMail($order));

        // --- ABA PayWay: render checkout page with signed form data ---
        if ($validated['payment_method'] === 'aba_pay') {
            $order->load('items');
            $paymentData = app(AbaPayService::class)->getPaymentFormData($order);

            return Inertia::render('Shop/AbaPayCheckout', [
                'orderNumber' => $order->order_number,
                'paymentData' => $paymentData,
            ]);
        }

        return redirect()->route('orders.confirmation', $order->order_number);
    }
}
```

---

## Backend — OrderController (Polling)

`app/Http/Controllers/Shop/OrderController.php`

The `paymentStatus()` method powers the frontend polling:

```php
<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\AbaPayService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function confirmation(string $orderNumber): Response
    {
        $order = Order::with('items')->where('order_number', $orderNumber)->firstOrFail();

        return Inertia::render('Shop/OrderConfirmation', [
            'order' => [
                'id'             => $order->id,
                'order_number'   => $order->order_number,
                'customer_name'  => $order->customer_name,
                'customer_email' => $order->customer_email,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'order_status'   => $order->order_status,
                'subtotal'       => $order->subtotal,
                'shipping'       => $order->shipping,
                'total'          => $order->total,
                'items'          => $order->items->map(fn($i) => [
                    'product_name' => $i->product_name,
                    'product_sku'  => $i->product_sku,
                    'unit_price'   => $i->unit_price,
                    'quantity'     => $i->quantity,
                    'subtotal'     => $i->subtotal,
                ]),
                'created_at' => $order->created_at->format('M d, Y H:i'),
            ],
        ]);
    }

    /**
     * API endpoint polled by the frontend to check payment status.
     * Also re-confirms with ABA if payment is still pending and not expired.
     */
    public function paymentStatus(string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        if ($order->payment_status === 'paid') {
            return response()->json(['paid' => true, 'status' => 'paid', 'expired' => false]);
        }

        // Expire after 15 minutes (matches ABA lifetime param)
        $expired = $order->created_at && $order->created_at->addMinutes(15)->isPast();

        if ($order->payment_method === 'aba_pay' && !$expired) {
            $result = app(AbaPayService::class)->checkPaymentStatus($order->order_number);

            if ($result['paid']) {
                $order->update([
                    'payment_status' => 'paid',
                    'order_status'   => 'confirmed',
                    'paid_at'        => now(),
                ]);
                return response()->json(['paid' => true, 'status' => 'paid', 'expired' => false]);
            }
        }

        return response()->json([
            'paid'    => false,
            'status'  => $order->payment_status,
            'expired' => $expired,
        ]);
    }
}
```

---

## Frontend — Blade Template (PayWay JS SDK)

`resources/views/app.blade.php`

The PayWay JS SDK **must** be loaded in the main Blade template. jQuery is required.

```html
<head>
    <!-- ... your other head tags ... -->

    <!-- jQuery (required by ABA PayWay checkout2-0.js) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- ABA PayWay popup checkout plugin -->
    <script src="https://checkout.payway.com.kh/plugins/checkout2-0.js"></script>

    <!-- ABA PayWay popup trigger — event delegation works with React SPA rendering -->
    <script>
        $(document).on('click', '#checkout_button', function () {
            $('#aba_merchant_request').append($('.payment_option:checked'));
            AbaPayway.checkout();
        });
    </script>

    @routes
    @viteReactRefresh
    @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
    @inertiaHead
</head>
<body class="font-sans antialiased">
    @inertia
</body>
```

**Why event delegation?** React re-renders the DOM, so `$(document).on('click', '#checkout_button', ...)` ensures the click handler works even after Inertia page navigations.

---

## Frontend — AbaPayCheckout.jsx

`resources/js/Pages/Shop/AbaPayCheckout.jsx`

This page auto-opens the PayWay popup and polls for payment status:

```jsx
import { Head, router } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Loader2 } from 'lucide-react';

export default function AbaPayCheckout({ orderNumber, paymentData }) {
    const { action, ...fields } = paymentData;
    const triggeredRef = useRef(false);

    // Auto-open PayWay popup on mount
    useEffect(() => {
        if (triggeredRef.current) return;
        triggeredRef.current = true;

        const t = setTimeout(() => {
            const btn = document.getElementById('checkout_button');
            if (btn) btn.click();
        }, 250);

        return () => clearTimeout(t);
    }, []);

    // Poll for payment status
    useEffect(() => {
        let interval;
        const timer = setTimeout(() => {
            const poll = async () => {
                try {
                    const res  = await fetch(`/api/orders/${orderNumber}/payment-status`);
                    const data = await res.json();
                    if (data.paid || data.expired) {
                        clearInterval(interval);
                        router.get(route('orders.confirmation', orderNumber));
                    }
                } catch {}
            };
            interval = setInterval(poll, 10000);
        }, 3000);

        return () => { clearTimeout(timer); clearInterval(interval); };
    }, [orderNumber]);

    return (
        <MainLayout>
            <Head title="ABA KHQR Checkout" />

            <div className="min-h-[60vh] flex flex-col items-center justify-center gap-6 px-4">
                <div className="text-center max-w-md">
                    <h1 className="font-bold text-2xl mb-2">Opening ABA KHQR...</h1>
                    <p className="text-sm mb-1">
                        Order: <span className="font-mono font-semibold">{orderNumber}</span>
                    </p>
                    <p className="text-xs text-gray-400 mb-6">
                        Scan the QR code in the popup with any banking app.
                    </p>

                    <div className="flex items-center justify-center gap-2 text-sm mb-6">
                        <Loader2 className="w-4 h-4 animate-spin" />
                        Launching secure payment window...
                    </div>

                    {/* Fallback button if auto-click doesn't work */}
                    <button
                        id="checkout_button"
                        type="button"
                        className="bg-red-600 hover:bg-red-700 text-white font-bold px-8 py-3 rounded-xl"
                    >
                        Open ABA KHQR Payment
                    </button>
                </div>
            </div>

            {/* Hidden form — PayWay JS reads these fields */}
            <form
                id="aba_merchant_request"
                method="POST"
                action={action}
                target="aba_webservice"
                className="hidden"
            >
                {Object.entries(fields).map(([name, value]) => (
                    <input key={name} type="hidden" name={name} value={value ?? ''} />
                ))}
            </form>
        </MainLayout>
    );
}
```

---

## Frontend — OrderConfirmation.jsx (Polling UI)

`resources/js/Pages/Shop/OrderConfirmation.jsx`

Shows order status with real-time polling for ABA payments:

```jsx
import { Head, Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { CheckCircle, Clock, ShoppingBag, Loader2, XCircle } from 'lucide-react';

function AbaPayStatusSection({ order, onPaid }) {
    const [paymentStatus, setPaymentStatus] = useState(order.payment_status);
    const [timeLeft, setTimeLeft] = useState(15 * 60); // 15 min countdown

    // Poll payment status every 10s (after initial 3s delay)
    useEffect(() => {
        if (paymentStatus === 'paid') return;

        let interval;
        const startTimer = setTimeout(() => {
            const poll = async () => {
                try {
                    const res  = await fetch(`/api/orders/${order.order_number}/payment-status`);
                    const data = await res.json();
                    if (data.paid) {
                        setPaymentStatus('paid');
                        onPaid();
                        clearInterval(interval);
                    } else if (data.expired) {
                        clearInterval(interval);
                        setTimeLeft(0);
                    }
                } catch {}
            };
            poll();
            interval = setInterval(poll, 10000);
        }, 3000);

        return () => { clearTimeout(startTimer); clearInterval(interval); };
    }, [order.order_number, paymentStatus]);

    // Countdown timer
    useEffect(() => {
        if (paymentStatus === 'paid' || timeLeft <= 0) return;
        const t = setTimeout(() => setTimeLeft(s => s - 1), 1000);
        return () => clearTimeout(t);
    }, [timeLeft, paymentStatus]);

    const minutes = Math.floor(timeLeft / 60).toString().padStart(2, '0');
    const seconds = (timeLeft % 60).toString().padStart(2, '0');

    if (paymentStatus === 'paid') {
        return (
            <div className="bg-green-50 border border-green-200 rounded-2xl p-8 text-center">
                <CheckCircle className="w-14 h-14 text-green-600 mx-auto mb-3" />
                <p className="font-bold text-xl mb-1">Payment Confirmed!</p>
                <p className="text-gray-500 text-sm">Your order is now being processed.</p>
            </div>
        );
    }

    if (timeLeft <= 0) {
        return (
            <div className="bg-red-50 border border-red-200 rounded-2xl p-8 text-center">
                <XCircle className="w-12 h-12 text-red-400 mx-auto mb-3" />
                <p className="font-bold text-lg mb-1">Session Expired</p>
                <p className="text-gray-500 text-sm">
                    If you completed payment, it will be confirmed shortly. Otherwise contact us.
                </p>
            </div>
        );
    }

    return (
        <div className="bg-white border rounded-2xl p-6 text-center">
            <h2 className="font-bold text-xl mb-1">Complete Your ABA KHQR Payment</h2>
            <p className="text-gray-500 text-sm mb-6">
                Scan the KHQR code in the popup with any banking app. Once paid, this page updates automatically.
            </p>
            <div className="flex items-center justify-center gap-3 mb-4">
                <Loader2 className="w-5 h-5 animate-spin" />
                <span className="text-sm">Waiting for payment confirmation...</span>
            </div>
            <div className="flex items-center justify-center gap-2 text-gray-400 text-sm">
                <Clock className="w-4 h-4" />
                <span>Session expires in <span className="font-mono font-bold">{minutes}:{seconds}</span></span>
            </div>
        </div>
    );
}

export default function OrderConfirmation({ order }) {
    const isAbaPay = order.payment_method === 'aba_pay';
    const [isPaid, setIsPaid] = useState(order.payment_status === 'paid');

    return (
        <MainLayout>
            <Head title={`Order ${order.order_number}`} />

            <div className="max-w-3xl mx-auto px-4 py-12">
                <div className="text-center mb-8">
                    <h1 className="font-bold text-3xl mb-2">
                        {isAbaPay && !isPaid ? 'Awaiting Payment' : 'Order Received!'}
                    </h1>
                    <div className="inline-block font-mono font-bold text-lg px-6 py-2.5 rounded-xl bg-green-50">
                        {order.order_number}
                    </div>
                    <p className="text-gray-400 text-sm mt-3">
                        Confirmation sent to <span className="font-medium">{order.customer_email}</span>
                    </p>
                </div>

                {isAbaPay && (
                    <div className="mb-8">
                        <AbaPayStatusSection order={order} onPaid={() => setIsPaid(true)} />
                    </div>
                )}

                {/* Order summary section */}
                <div className="bg-white border rounded-2xl overflow-hidden mb-8">
                    <div className="px-6 py-4 border-b bg-gray-50">
                        <h2 className="font-bold">Order Summary</h2>
                    </div>
                    <div className="p-6">
                        {order.items.map((item, i) => (
                            <div key={i} className="flex justify-between text-sm mb-2">
                                <span>{item.product_name} x{item.quantity}</span>
                                <span>${Number(item.subtotal).toFixed(2)}</span>
                            </div>
                        ))}
                        <div className="border-t pt-3 mt-3 space-y-1 text-sm">
                            <div className="flex justify-between">
                                <span>Subtotal</span>
                                <span>${Number(order.subtotal).toFixed(2)}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>Shipping</span>
                                <span>{Number(order.shipping) === 0 ? 'FREE' : `$${Number(order.shipping).toFixed(2)}`}</span>
                            </div>
                            <div className="flex justify-between font-bold text-base pt-1">
                                <span>Total</span>
                                <span>${Number(order.total).toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="text-center">
                    <Link href={route('shop.index')} className="inline-flex items-center gap-2 border-2 px-8 py-3 rounded-xl">
                        <ShoppingBag className="w-4 h-4" /> Continue Shopping
                    </Link>
                </div>
            </div>
        </MainLayout>
    );
}
```

---

## Route Registration

### Web routes (`routes/web.php`)

```php
// Checkout
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/orders/{orderNumber}/confirmation', [OrderController::class, 'confirmation'])->name('orders.confirmation');
```

### API routes (`routes/api.php`)

```php
use App\Http\Controllers\Shop\OrderController;

Route::get('/orders/{orderNumber}/payment-status', [OrderController::class, 'paymentStatus']);
```

### Bootstrap (`bootstrap/app.php`)

**The webhook route MUST be registered outside the `web` middleware group** to avoid session/Inertia/cookie middleware interfering with the plain-text response.

```php
<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Webhook routes — no middleware (no session, no Inertia, no CSRF)
            Route::post('/webhooks/aba-pay', [WebhookController::class, 'abaPay'])
                ->name('webhooks.aba')
                ->withoutMiddleware('*');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Webhook routes must always return plain text, never HTML error pages
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('webhooks/*')) {
                return response('Completed', 200)->header('Content-Type', 'text/plain');
            }
        });
    })->create();
```

---

## Mail & Events

### OrderPaid Event (`app/Events/OrderPaid.php`)

```php
<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPaid
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Order $order) {}
}
```

### OrderPaidMail (`app/Mail/OrderPaidMail.php`)

```php
<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPaidMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Payment Confirmed — {$this->order->order_number}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order-paid');
    }
}
```

### OrderConfirmationMail (`app/Mail/OrderConfirmationMail.php`)

```php
<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Order Confirmation — {$this->order->order_number}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order-confirmation');
    }
}
```

---

## Payment Flow (Step by Step)

```
1. Customer fills checkout form (3 steps: info → payment method → review)
         │
2. POST /checkout → CheckoutController::store()
   - Creates Order (payment_status=pending) + OrderItems
   - Clears cart
   - Queues OrderConfirmationMail
   - If ABA: returns AbaPayCheckout page with signed form fields
         │
3. AbaPayCheckout page loads
   - Auto-clicks #checkout_button after 250ms
   - jQuery event delegation calls AbaPayway.checkout()
   - PayWay popup opens showing KHQR QR code
   - Frontend starts polling /api/orders/{n}/payment-status every 10s
         │
4. Customer scans QR with any banking app and pays
         │
5. ABA sends webhook POST to /webhooks/aba-pay
   - WebhookController verifies signature
   - Re-confirms via check-transaction-2 API
   - Updates order: payment_status=paid, order_status=confirmed
   - Dispatches OrderPaid event → queues OrderPaidMail
   - Returns plain text "Completed" (200)
         │
6. Frontend polling detects paid=true
   - AbaPayCheckout: redirects to /orders/{n}/confirmation
   - OrderConfirmation: updates UI to show "Payment Confirmed!"
         │
7. ABA popup shows success page
   - "Continues Shopping" button → redirects to home page (continue_success_url)
```

---

## Polling Strategy

Per ABA team requirements:

| Stage | Timing | Stops when |
|---|---|---|
| Initial delay (AbaPayCheckout) | 3s after page load | — |
| Active polling (AbaPayCheckout) | Every 10s | `paid=true`, `expired=true`, or page unmount |
| Initial delay (OrderConfirmation) | 3s after page load | — |
| Active polling (OrderConfirmation) | Every 10s | `paid=true`, `expired=true`, or page unmount |
| Webhook reconfirmation | Single check-transaction-2 call on webhook receipt | — |
| Maximum polling duration | 15 min (= `lifetime` param) | Backend returns `expired=true` once `order.created_at + 15min` has passed |
| Countdown timer | 1s ticks on OrderConfirmation | Reaches 00:00 → shows "Session Expired" |

---

## Gotchas & Lessons Learned

### 1. Webhook MUST return plain text, not HTML
ABA rejects HTML error pages. If your webhook endpoint goes through web middleware (session, Inertia, etc.) and anything throws, ABA gets an HTML 500 page and reports an error.

**Fix**: Register the webhook route **outside the `web` middleware group** and wrap everything in try-catch.

### 2. `payment_option = abapay_khqr` is required
Omitting it causes the generic PayWay chooser to render instead of directly showing the KHQR QR code.

### 3. `return_url` must be Base64-encoded
ABA's integration requires `return_url` to be Base64-encoded before submitting. Other URLs (cancel, continue_success) are NOT encoded.

### 4. Do NOT send a `payout` field
Sending it triggers: _"payout service is not available on your profile"_. Remove it entirely.

### 5. Filter out empty/null params
ABA rejects requests containing `null` or empty string fields with: _"Please remove null params from your request"_. Use `array_filter()` on the form data.

### 6. Do NOT `sleep()` in the webhook handler
ABA will timeout the pushback connection. Re-confirm via check-transaction-2 API immediately, no delay.

### 7. Domain whitelist (Error Code 6)
If you see _"Unable to process — Requested Domain is not in whitelist"_:
1. Log in to ABA PayWay merchant portal
2. Go to **Settings → Integration**
3. Add your domain (e.g. `localhost`, `localhost:8000`, or production domain)

### 8. Hash field order matters
The hash data must be concatenated in the exact order specified by ABA's API documentation. Even empty strings must be included in the concatenation in their correct position.

### 9. Phone format must be 855XXXXXXXXX
Cambodian phones must be normalized: strip non-digits, replace leading `0` with `855`.

### 10. jQuery is required
The PayWay JS SDK (`checkout2-0.js`) depends on jQuery. Load it before the SDK script.

### 11. Use event delegation for the checkout button
Since React re-renders the DOM, use `$(document).on('click', '#checkout_button', ...)` instead of direct binding.

### 12. `continue_success_url` controls "Continues Shopping" button
After payment succeeds in the ABA popup, the "Continues Shopping" button redirects to this URL. Set it to your home page, not the confirmation page (otherwise users land on "Awaiting Payment" after already paying).

---

## Quick Setup Checklist

- [ ] Add ABA env vars to `.env`
- [ ] Add ABA config to `config/services.php`
- [ ] Create `AbaPayService` class
- [ ] Create `WebhookController` with try-catch
- [ ] Register webhook route in `bootstrap/app.php` (outside web middleware)
- [ ] Add exception handler for webhook routes (plain-text response)
- [ ] Add payment status API route to `routes/api.php`
- [ ] Add jQuery + PayWay JS SDK to Blade template
- [ ] Add event delegation script for `#checkout_button`
- [ ] Create `AbaPayCheckout` page (auto-open popup + polling)
- [ ] Create `OrderConfirmation` page (status polling + countdown)
- [ ] Add `aba_transaction_id` and `paid_at` columns to orders table
- [ ] Create `OrderPaid` event and `OrderPaidMail`
- [ ] Whitelist your domain in ABA PayWay merchant portal
- [ ] Test webhook with: `curl -X POST /webhooks/aba-pay -d "tran_id=test&hash=invalid"` → expect `Completed`

---
---

# Part 2 — Payout to Vendors

---

## Payout Overview

ABA PayWay's **Multi-Party Payout** feature lets you distribute funds from your merchant account to vendors, partners, or beneficiaries in real-time. This is essential for **e-commerce marketplaces** where you collect payment from the customer and split it to multiple vendors.

**Two payout approaches:**

| Approach | Use Case |
|----------|----------|
| **Direct Payout** | Collect payment first, then initiate payout separately (most common for e-commerce) |
| **Pre-Auth with Payout** | Hold customer's payment, then capture + distribute to vendors in one step |

**Key rules:**
- Beneficiaries (vendors) **must be whitelisted** before any payout
- Beneficiaries can be either an **ABA account number** or a **Merchant ID (MID)**
- Maximum **10 beneficiaries per payout request**
- Minimum amounts: **100 KHR** or **0.01 USD**
- Payout uses **RSA encryption** for the beneficiaries field (unlike checkout which only uses HMAC)

---

## Payout Environment Setup

### Additional .env variables (on top of checkout vars)

```env
# Same merchant credentials as checkout
ABA_MERCHANT_ID=your_merchant_id
ABA_API_KEY=your_api_key_here
ABA_PAYWAY_URL=https://checkout-sandbox.payway.com.kh

# NEW — RSA public key for payout encryption
ABA_RSA_PUBLIC_KEY_PATH=storage/app/aba_payway_public.pem
```

### config/services.php — add RSA key path

```php
'aba' => [
    'merchant_id'        => env('ABA_MERCHANT_ID', ''),
    'merchant_name'      => env('ABA_MERCHANT_NAME', ''),
    'api_key'            => env('ABA_API_KEY', ''),
    'payway_url'         => env('ABA_PAYWAY_URL', 'https://checkout-sandbox.payway.com.kh'),
    'rsa_public_key_path' => env('ABA_RSA_PUBLIC_KEY_PATH', 'storage/app/aba_payway_public.pem'),
],
```

> **Important**: ABA provides the RSA public key during merchant onboarding. Save it as a `.pem` file. Contact ABA team if you don't have it.

---

## RSA Encryption (Required for Payout)

Payout and beneficiary APIs require **RSA-encrypted** fields. ABA uses chunked RSA encryption because the payload can exceed the standard RSA block size.

### PHP Implementation

```php
/**
 * RSA-encrypt data in 117-byte chunks using ABA's public key.
 * Required for: beneficiaries (payout), merchant_auth (add/update beneficiary)
 */
private function rsaEncrypt(string $data): string
{
    $publicKeyPath = base_path(config('services.aba.rsa_public_key_path'));
    $publicKey = openssl_pkey_get_public(file_get_contents($publicKeyPath));

    if (!$publicKey) {
        throw new \RuntimeException('Failed to load ABA RSA public key');
    }

    $encrypted = '';
    $chunks = str_split($data, 117); // RSA max chunk size

    foreach ($chunks as $chunk) {
        $encryptedChunk = '';
        openssl_public_encrypt($chunk, $encryptedChunk, $publicKey);
        $encrypted .= $encryptedChunk;
    }

    return base64_encode($encrypted);
}
```

---

## Add Beneficiary (Whitelist Vendor)

Before you can pay out to a vendor, you **must whitelist them** using this API.

### Endpoint

```
POST {payway_url}/api/merchant-portal/merchant-access/whitelist-account/add-whitelist-payout
Content-Type: application/json
```

### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `request_time` | string | Yes | UTC datetime as `YYYYMMDDHHmmss` |
| `merchant_id` | string | Yes | Your merchant ID |
| `merchant_auth` | string | Yes | RSA-encrypted JSON (see below) |
| `hash` | string | Yes | HMAC-SHA512 of `request_time` + `merchant_auth` |

### merchant_auth object (before encryption)

```json
{
    "mc_id": "your_merchant_id",
    "payee": "200030000"
}
```

- `payee` can be an **ABA account number** or a **Merchant ID (MID)**

### PHP Example

```php
public function addBeneficiary(string $payee): array
{
    $reqTime = now()->utc()->format('YmdHis');

    $merchantAuth = $this->rsaEncrypt(json_encode([
        'mc_id' => $this->merchantId,
        'payee' => $payee,
    ]));

    $hash = $this->sign($reqTime . $merchantAuth);

    $response = Http::timeout(15)
        ->asJson()
        ->post("{$this->paywayUrl}/api/merchant-portal/merchant-access/whitelist-account/add-whitelist-payout", [
            'request_time'  => $reqTime,
            'merchant_id'   => $this->merchantId,
            'merchant_auth' => $merchantAuth,
            'hash'          => $hash,
        ]);

    return $response->json();
}
```

### Success Response

```json
{
    "data": {
        "name": "Vendor Store Name",
        "payee": "200030000",
        "currency": "USD",
        "type": "ABA Account",
        "status": 1,
        "created_at": "2024-08-23T10:35:55Z"
    },
    "status": { "code": "200", "message": "Success" }
}
```

### Error Codes

| Code | Meaning |
|------|---------|
| PTL02 | Wrong hash |
| PTL04 | Parameter validation error |
| PTL25 | Invalid account class |
| PTL99 | Merchant invalid currency |
| PTL134 | Account not found |
| PTL146 | Payee is invalid |
| PTL147 | Currency mismatch between payee and merchant |
| PTL148 | Payee already exists |
| PTL150 | Business profile not found |
| PTL151 | Failed to whitelist account |

---

## Update Beneficiary Status

Activate or deactivate a whitelisted beneficiary.

### Endpoint

```
POST {payway_url}/api/merchant-portal/merchant-access/whitelist-account/update-whitelist-status
Content-Type: application/json
```

### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `request_time` | string | Yes | UTC datetime as `YYYYMMDDHHmmss` |
| `merchant_id` | string | Yes | Your merchant ID |
| `merchant_auth` | string | Yes | RSA-encrypted JSON (see below) |
| `hash` | string | Yes | HMAC-SHA512 of `request_time` + `merchant_auth` |

### merchant_auth object (before encryption)

```json
{
    "mc_id": "your_merchant_id",
    "payee": "200030000",
    "status": 1
}
```

- `status`: `1` = Active, `0` = Inactive

### PHP Example

```php
public function updateBeneficiaryStatus(string $payee, int $status): array
{
    $reqTime = now()->utc()->format('YmdHis');

    $merchantAuth = $this->rsaEncrypt(json_encode([
        'mc_id'  => $this->merchantId,
        'payee'  => $payee,
        'status' => $status,
    ]));

    $hash = $this->sign($reqTime . $merchantAuth);

    $response = Http::timeout(15)
        ->asJson()
        ->post("{$this->paywayUrl}/api/merchant-portal/merchant-access/whitelist-account/update-whitelist-status", [
            'request_time'  => $reqTime,
            'merchant_id'   => $this->merchantId,
            'merchant_auth' => $merchantAuth,
            'hash'          => $hash,
        ]);

    return $response->json();
}
```

### Error Codes

| Code | Meaning |
|------|---------|
| PTL02 | Hash validation failed |
| PTL04 | Parameter validation error |
| PTL46 | Merchant not found |
| PTL149 | Invalid whitelist account |
| PTL150 | Business profile missing |

---

## Execute Payout

Distribute funds to whitelisted beneficiaries (vendors).

### Endpoint

```
POST {payway_url}/api/payment-gateway/v2/direct-payment/merchant/payout
Content-Type: application/json
```

### Request Parameters

| Parameter | Type | Max Length | Required | Description |
|-----------|------|-----------|----------|-------------|
| `merchant_id` | string | 255 | Yes | Your merchant ID |
| `tran_id` | string | 20 | Yes | Unique transaction identifier |
| `beneficiaries` | string | 1000 | Yes | **RSA-encrypted** JSON array of beneficiaries |
| `amount` | number | — | Yes | Total payout amount (KHR min: 100, USD min: 0.01) |
| `currency` | string | 3 | Yes | `KHR` or `USD` |
| `custom_fields` | string | 255 | No | Additional JSON metadata |
| `hash` | string | 512 | Yes | HMAC-SHA512 signature |

### Beneficiaries array (before RSA encryption)

```json
[
    {"account": "200030000", "amount": 100.00},
    {"account": "012538302", "amount": 50.00}
]
```

- Maximum **10 beneficiaries** per request
- `account` = ABA account number or Merchant ID
- `amount` = individual amount for this beneficiary

### Hash Generation Order

```
merchant_id + tran_id + beneficiaries(encrypted) + amount + custom_fields + currency
```

### PHP Example

```php
public function payout(string $tranId, array $beneficiaries, float $amount, string $currency = 'USD', ?string $customFields = null): array
{
    $beneficiariesJson = json_encode($beneficiaries);
    $encryptedBeneficiaries = $this->rsaEncrypt($beneficiariesJson);

    $hashData = implode('', [
        $this->merchantId,
        $tranId,
        $encryptedBeneficiaries,
        $amount,
        $customFields ?? '',
        $currency,
    ]);
    $hash = $this->sign($hashData);

    $payload = array_filter([
        'merchant_id'   => $this->merchantId,
        'tran_id'       => $tranId,
        'beneficiaries' => $encryptedBeneficiaries,
        'amount'        => $amount,
        'currency'      => $currency,
        'custom_fields' => $customFields,
        'hash'          => $hash,
    ], fn($v) => $v !== null);

    $response = Http::timeout(15)
        ->asJson()
        ->post("{$this->paywayUrl}/api/payment-gateway/v2/direct-payment/merchant/payout", $payload);

    return $response->json();
}
```

### Success Response

```json
{
    "transaction_id": "A17259584044451",
    "transaction_date": "2024-08-23 10:35:55",
    "external_reference": "FT2408230001",
    "apv": "123456",
    "transaction_amount": 150.00,
    "transaction_currency": "USD",
    "beneficiaries": [
        {
            "payout_id": "PO_001",
            "name": "Vendor A",
            "mid_account": "200030000",
            "amount": 100.00,
            "currency": "USD"
        },
        {
            "payout_id": "PO_002",
            "name": "Vendor B",
            "mid_account": "012538302",
            "amount": 50.00,
            "currency": "USD"
        }
    ],
    "status": { "code": "0", "message": "Success" }
}
```

---

## Pre-Auth with Payout (Hold & Split)

For marketplace scenarios: hold the customer's payment, then capture and distribute to vendors in one step.

### Flow
1. Customer pays → funds are **held** (pre-authorized, not captured)
2. You verify order/delivery
3. Call **Complete Pre-Auth with Payout** → captures funds + distributes to vendors simultaneously

### Endpoint

```
POST {payway_url}/api/merchant-portal/merchant-access/online-transaction/pre-auth-completion
Content-Type: application/json
```

### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `request_time` | string | Yes | UTC datetime as `YYYYMMDDHHmmss` |
| `merchant_id` | string | Yes | Your merchant ID |
| `merchant_auth` | string | Yes | RSA-encrypted JSON (see below) |
| `hash` | string | Yes | HMAC-SHA512 of `merchant_auth` + `request_time` + `merchant_id` |

> **Note**: Hash order is different here: `merchant_auth` + `request_time` + `merchant_id`

### merchant_auth object (before encryption)

```json
{
    "mc_id": "your_merchant_id",
    "tran_id": "ORIGINAL_PREAUTH_TRAN_ID",
    "complete_amount": 150.00,
    "payout": [
        {"acc": "200030000", "amt": 100.00},
        {"acc": "012538302", "amt": 50.00}
    ]
}
```

- `tran_id` = the original pre-authorization transaction ID
- `complete_amount` = amount to capture (max 110% of original for card payments)
- `payout` = distribution array with `acc` (account/MID) and `amt` (amount)

### PHP Example

```php
public function completePreAuthWithPayout(string $originalTranId, float $amount, array $payoutDistribution): array
{
    $reqTime = now()->utc()->format('YmdHis');

    $merchantAuth = $this->rsaEncrypt(json_encode([
        'mc_id'           => $this->merchantId,
        'tran_id'         => $originalTranId,
        'complete_amount' => $amount,
        'payout'          => $payoutDistribution, // [{"acc": "...", "amt": 100}, ...]
    ]));

    // Note: hash order is different from other endpoints!
    $hash = $this->sign($merchantAuth . $reqTime . $this->merchantId);

    $response = Http::timeout(15)
        ->asJson()
        ->post("{$this->paywayUrl}/api/merchant-portal/merchant-access/online-transaction/pre-auth-completion", [
            'request_time'  => $reqTime,
            'merchant_id'   => $this->merchantId,
            'merchant_auth' => $merchantAuth,
            'hash'          => $hash,
        ]);

    return $response->json();
}
```

### Success Response

```json
{
    "grand_total": 150.00,
    "currency": "USD",
    "transaction_status": "COMPLETED",
    "status": { "code": "00", "message": "Transaction successful" }
}
```

### Key Rules
- Pre-auth completion is a **one-time action** — cannot be retried
- Cannot complete **expired or cancelled** pre-auth transactions
- Card payments allow capture up to **110%** of original authorization
- All beneficiaries must be whitelisted first

---

## Get Transactions by Reference

Look up transactions by your merchant reference number. Useful for reconciliation.

### Endpoint

```
POST {payway_url}/api/payment-gateway/v1/payments/get-transactions-by-mc-ref
Content-Type: application/json
```

**Rate limit**: 10 requests per minute (cannot be increased)

### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `req_time` | string | Yes | UTC datetime as `YYYYMMDDHHmmss` |
| `merchant_id` | string | Yes | Your merchant ID |
| `merchant_ref` | string | Yes | Your merchant reference number |
| `hash` | string | Yes | HMAC-SHA512 of `req_time` + `merchant_id` + `merchant_ref` |

### PHP Example

```php
public function getTransactionsByRef(string $merchantRef): array
{
    $reqTime = now()->utc()->format('YmdHis');
    $hash = $this->sign($reqTime . $this->merchantId . $merchantRef);

    $response = Http::timeout(15)
        ->asJson()
        ->post("{$this->paywayUrl}/api/payment-gateway/v1/payments/get-transactions-by-mc-ref", [
            'req_time'     => $reqTime,
            'merchant_id'  => $this->merchantId,
            'merchant_ref' => $merchantRef,
            'hash'         => $hash,
        ]);

    return $response->json();
}
```

### Response

Returns the **last 50 transactions** matching the reference, with fields including: `transaction_id`, `transaction_date`, `payment_status` (APPROVED/REFUNDED), `original_amount`, `original_currency`, `payment_type` (ABA Pay/KHQR), etc.

---

## Payout Laravel Service

Complete service class for all payout operations.

`app/Services/AbaPayoutService.php`

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AbaPayoutService
{
    private string $merchantId;
    private string $apiKey;
    private string $paywayUrl;
    private string $rsaPublicKeyPath;

    public function __construct()
    {
        $this->merchantId       = config('services.aba.merchant_id', '');
        $this->apiKey           = config('services.aba.api_key', '');
        $this->paywayUrl        = rtrim(config('services.aba.payway_url', 'https://checkout-sandbox.payway.com.kh'), '/');
        $this->rsaPublicKeyPath = base_path(config('services.aba.rsa_public_key_path'));
    }

    // ──────────────────────────────────────────────
    // Beneficiary Management
    // ──────────────────────────────────────────────

    /**
     * Whitelist a beneficiary (vendor) for payout.
     * Must be called before any payout to this account.
     *
     * @param string $payee ABA account number or Merchant ID
     */
    public function addBeneficiary(string $payee): array
    {
        $reqTime = now()->utc()->format('YmdHis');

        $merchantAuth = $this->rsaEncrypt(json_encode([
            'mc_id' => $this->merchantId,
            'payee' => $payee,
        ]));

        $hash = $this->sign($reqTime . $merchantAuth);

        return $this->post(
            '/api/merchant-portal/merchant-access/whitelist-account/add-whitelist-payout',
            [
                'request_time'  => $reqTime,
                'merchant_id'   => $this->merchantId,
                'merchant_auth' => $merchantAuth,
                'hash'          => $hash,
            ]
        );
    }

    /**
     * Activate or deactivate a whitelisted beneficiary.
     *
     * @param string $payee  ABA account number or Merchant ID
     * @param int    $status 1 = Active, 0 = Inactive
     */
    public function updateBeneficiaryStatus(string $payee, int $status): array
    {
        $reqTime = now()->utc()->format('YmdHis');

        $merchantAuth = $this->rsaEncrypt(json_encode([
            'mc_id'  => $this->merchantId,
            'payee'  => $payee,
            'status' => $status,
        ]));

        $hash = $this->sign($reqTime . $merchantAuth);

        return $this->post(
            '/api/merchant-portal/merchant-access/whitelist-account/update-whitelist-status',
            [
                'request_time'  => $reqTime,
                'merchant_id'   => $this->merchantId,
                'merchant_auth' => $merchantAuth,
                'hash'          => $hash,
            ]
        );
    }

    // ──────────────────────────────────────────────
    // Payout
    // ──────────────────────────────────────────────

    /**
     * Distribute funds to whitelisted beneficiaries.
     *
     * @param string      $tranId        Unique transaction ID (max 20 chars)
     * @param array       $beneficiaries [["account" => "...", "amount" => 100], ...]  (max 10)
     * @param float       $amount        Total payout amount
     * @param string      $currency      "USD" or "KHR"
     * @param string|null $customFields  Optional JSON metadata
     */
    public function payout(string $tranId, array $beneficiaries, float $amount, string $currency = 'USD', ?string $customFields = null): array
    {
        $encryptedBeneficiaries = $this->rsaEncrypt(json_encode($beneficiaries));

        $hashData = implode('', [
            $this->merchantId,
            $tranId,
            $encryptedBeneficiaries,
            $amount,
            $customFields ?? '',
            $currency,
        ]);

        $payload = array_filter([
            'merchant_id'   => $this->merchantId,
            'tran_id'       => $tranId,
            'beneficiaries' => $encryptedBeneficiaries,
            'amount'        => $amount,
            'currency'      => $currency,
            'custom_fields' => $customFields,
            'hash'          => $this->sign($hashData),
        ], fn($v) => $v !== null);

        return $this->post('/api/payment-gateway/v2/direct-payment/merchant/payout', $payload);
    }

    // ──────────────────────────────────────────────
    // Pre-Auth + Payout (Hold & Split)
    // ──────────────────────────────────────────────

    /**
     * Complete a pre-authorized transaction and distribute to vendors.
     *
     * @param string $originalTranId     Original pre-auth transaction ID
     * @param float  $amount             Amount to capture (max 110% of original for cards)
     * @param array  $payoutDistribution [["acc" => "...", "amt" => 100], ...]
     */
    public function completePreAuthWithPayout(string $originalTranId, float $amount, array $payoutDistribution): array
    {
        $reqTime = now()->utc()->format('YmdHis');

        $merchantAuth = $this->rsaEncrypt(json_encode([
            'mc_id'           => $this->merchantId,
            'tran_id'         => $originalTranId,
            'complete_amount' => $amount,
            'payout'          => $payoutDistribution,
        ]));

        // Hash order is different: merchant_auth + request_time + merchant_id
        $hash = $this->sign($merchantAuth . $reqTime . $this->merchantId);

        return $this->post(
            '/api/merchant-portal/merchant-access/online-transaction/pre-auth-completion',
            [
                'request_time'  => $reqTime,
                'merchant_id'   => $this->merchantId,
                'merchant_auth' => $merchantAuth,
                'hash'          => $hash,
            ]
        );
    }

    // ──────────────────────────────────────────────
    // Transaction Lookup
    // ──────────────────────────────────────────────

    /**
     * Look up transactions by merchant reference (max 50 results).
     * Rate limited: 10 requests/minute.
     */
    public function getTransactionsByRef(string $merchantRef): array
    {
        $reqTime = now()->utc()->format('YmdHis');
        $hash = $this->sign($reqTime . $this->merchantId . $merchantRef);

        return $this->post(
            '/api/payment-gateway/v1/payments/get-transactions-by-mc-ref',
            [
                'req_time'     => $reqTime,
                'merchant_id'  => $this->merchantId,
                'merchant_ref' => $merchantRef,
                'hash'         => $hash,
            ]
        );
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    /**
     * RSA-encrypt data in 117-byte chunks using ABA's public key.
     */
    private function rsaEncrypt(string $data): string
    {
        $publicKey = openssl_pkey_get_public(file_get_contents($this->rsaPublicKeyPath));

        if (!$publicKey) {
            throw new \RuntimeException('Failed to load ABA RSA public key from: ' . $this->rsaPublicKeyPath);
        }

        $encrypted = '';
        $chunks = str_split($data, 117);

        foreach ($chunks as $chunk) {
            $encryptedChunk = '';
            openssl_public_encrypt($chunk, $encryptedChunk, $publicKey);
            $encrypted .= $encryptedChunk;
        }

        return base64_encode($encrypted);
    }

    private function sign(string $data): string
    {
        return base64_encode(hash_hmac('sha512', $data, $this->apiKey, true));
    }

    private function post(string $path, array $payload): array
    {
        try {
            $response = Http::timeout(15)
                ->asJson()
                ->post("{$this->paywayUrl}{$path}", $payload);

            return $response->json() ?? ['status' => ['code' => 'UNKNOWN', 'message' => 'Empty response']];
        } catch (\Throwable $e) {
            Log::error('ABA PayWay payout API error', [
                'path'    => $path,
                'message' => $e->getMessage(),
            ]);

            return ['status' => ['code' => 'ERROR', 'message' => $e->getMessage()]];
        }
    }
}
```

---

## Payout Status Codes

### Payout API (Direct)

| Code | Meaning |
|------|---------|
| 0 | Success |
| 4 | Duplicate transaction ID |
| 11 | Processing error |
| 24 | RSA decryption failure |
| 25 | Exceeds 10 beneficiary limit |
| 26 | Invalid merchant profile |
| 36 | Invalid payout account or amount |
| 37 | Beneficiary not whitelisted |
| 44 | Transaction limit reached |
| 48 | Processing error |
| 70 | Daily limit exceeded |
| 79 | Payment rejected |
| 80 | Invalid custom fields |
| 81 | Zero or negative amount |
| 82 | Unsupported currency |
| 83 | Duplicate transaction |
| 84 | Account access error |
| 85-93 | Balance, currency mismatch, or account errors |
| 400 | Bad request |
| LAM01 | Daily payout limit exceeded |
| LAM02 | Monthly payout limit exceeded |

### Beneficiary API

| Code | Meaning |
|------|---------|
| 200 / 00 | Success |
| PTL02 | Wrong hash |
| PTL04 | Parameter validation error |
| PTL25 | Invalid account class |
| PTL46 | Merchant not found |
| PTL99 | Merchant invalid currency |
| PTL134 | Account not found |
| PTL146 | Payee is invalid |
| PTL147 | Currency mismatch |
| PTL148 | Payee already exists (already whitelisted) |
| PTL149 | Invalid whitelist account |
| PTL150 | Business profile not found |
| PTL151 | Failed to whitelist account |

### Pre-Auth Completion

| Code | Meaning |
|------|---------|
| 00 | Success |
| PTL02 | Hash validation failed |
| PTL60 | Completion amount exceeds 110% limit |
| PTL153 | Multi-account merchants cannot use fees with pre-auth |

---

## Payout Flow (Step by Step)

### Direct Payout (most common for e-commerce)

```
1. Vendor registers on your platform
   - You collect their ABA account number or MID
         │
2. Whitelist vendor (one-time setup)
   - Call addBeneficiary("vendor_aba_account")
   - Store vendor's whitelist status in your DB
         │
3. Customer places order and pays via KHQR checkout
   - Payment is collected into YOUR merchant account
   - (This is the Part 1 checkout flow)
         │
4. Order fulfilled / ready for settlement
   - You decide the vendor split (e.g., 80% vendor, 20% platform fee)
         │
5. Execute payout
   - Call payout(tranId, [{account: "vendor_acc", amount: 80}], 80, "USD")
   - Funds transfer in real-time from your account to vendor's
         │
6. Record payout in your DB
   - Store transaction_id, payout amounts, status
   - Use getTransactionsByRef() for reconciliation
```

### Pre-Auth + Payout (marketplace hold & split)

```
1. Customer pays → funds are HELD (pre-authorized, not captured)
         │
2. Vendor confirms order / delivers goods
         │
3. Complete pre-auth with payout split
   - Call completePreAuthWithPayout(originalTranId, totalAmount, [
       {acc: "vendor_acc", amt: 80},
       {acc: "platform_acc", amt: 20}
     ])
   - Captures held funds + distributes to vendors in one API call
         │
4. Transaction is final — cannot be retried or reversed via API
```

---

## Payout Quick Setup Checklist

- [ ] Obtain RSA public key from ABA team
- [ ] Save RSA key as `.pem` file in `storage/app/`
- [ ] Add `ABA_RSA_PUBLIC_KEY_PATH` to `.env`
- [ ] Update `config/services.php` with RSA key path
- [ ] Create `AbaPayoutService` class
- [ ] Whitelist all vendor accounts using `addBeneficiary()`
- [ ] Store vendor whitelist status in your vendors table
- [ ] Implement payout trigger (e.g., after order delivery confirmed)
- [ ] Add payout transaction logging to your DB
- [ ] Test payout in sandbox with test accounts
- [ ] Set up reconciliation using `getTransactionsByRef()`
- [ ] Contact ABA team for production RSA key and payout limits
