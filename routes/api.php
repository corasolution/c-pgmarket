<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AddressApiController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartApiController;
use App\Http\Controllers\Api\V1\CategoryApiController;
use App\Http\Controllers\Api\V1\CheckoutApiController;
use App\Http\Controllers\Api\V1\FavoriteApiController;
use App\Http\Controllers\Api\V1\MobileHomeController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\DeviceTokenController;
use App\Http\Controllers\Api\V1\ShopApiController;
use Illuminate\Support\Facades\Route;

/*
 * Mobile / third-party API (Sanctum token-based, NOT Inertia session auth)
 * Rate-limiting is applied at the route level per CLAUDE.md security rules.
 */

Route::prefix('v1')->name('api.v1.')->middleware(['throttle:60,1'])->group(function (): void {
    // Auth endpoints — rate-limited to 5/min
    Route::middleware(['throttle:5,1'])->group(function (): void {
        Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');
        Route::post('/auth/register', [AuthController::class, 'register'])->name('auth.register');
        Route::post('/auth/google', [AuthController::class, 'googleLogin'])->name('auth.google.mobile');
    });

    // ── Public endpoints (no auth required) ──────────────────────────────
    Route::get('/home', MobileHomeController::class)->name('home');

    Route::get('/categories', [CategoryApiController::class, 'index'])->name('categories.index');
    Route::get('/categories/{slug}', [CategoryApiController::class, 'show'])->name('categories.show');

    Route::get('/shops', [ShopApiController::class, 'index'])->name('shops.index');
    Route::get('/shops/{slug}', [ShopApiController::class, 'show'])->name('shops.show');

    // Products (public — browsable without login)
    Route::apiResource('products', ProductController::class)->only(['index', 'show']);

    // ── Authenticated endpoints ──────────────────────────────────────────
    Route::middleware(['auth:sanctum'])->group(function (): void {
        Route::delete('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::put('/auth/profile', [AuthController::class, 'updateProfile'])->name('auth.profile.update');

        // Orders — rate-limited to 10/min per user
        Route::middleware(['throttle:10,1'])->group(function (): void {
            Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);
            Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        });

        // Cart
        Route::get('/cart', [CartApiController::class, 'index'])->name('cart.index');
        Route::post('/cart', [CartApiController::class, 'store'])->name('cart.store');
        Route::put('/cart/{itemId}', [CartApiController::class, 'update'])->name('cart.update');
        Route::delete('/cart/{itemId}', [CartApiController::class, 'destroy'])->name('cart.destroy');

        // Addresses
        Route::get('/addresses', [AddressApiController::class, 'index'])->name('addresses.index');
        Route::post('/addresses', [AddressApiController::class, 'store'])->name('addresses.store');
        Route::put('/addresses/{address}', [AddressApiController::class, 'update'])->name('addresses.update');
        Route::delete('/addresses/{address}', [AddressApiController::class, 'destroy'])->name('addresses.destroy');
        Route::patch('/addresses/{address}/default', [AddressApiController::class, 'setDefault'])->name('addresses.default');

        // Checkout — rate-limited to 10/min
        Route::middleware(['throttle:10,1'])->group(function (): void {
            Route::post('/checkout', [CheckoutApiController::class, 'store'])->name('checkout.store');
        });
        Route::get('/checkout/poll/{transaction}', [CheckoutApiController::class, 'poll'])->name('checkout.poll');

        // Favorites
        Route::get('/favorites', [FavoriteApiController::class, 'index'])->name('favorites.index');
        Route::post('/favorites/{product}', [FavoriteApiController::class, 'toggle'])->name('favorites.toggle');

        // Device tokens (push notifications)
        Route::post('/device-tokens', [DeviceTokenController::class, 'store'])->name('device-tokens.store');
        Route::delete('/device-tokens', [DeviceTokenController::class, 'destroy'])->name('device-tokens.destroy');
    });
});
