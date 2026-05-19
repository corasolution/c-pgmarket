<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Vendor\ProductExportController;
use App\Http\Controllers\Storefront\CategoryController;
use App\Http\Controllers\Storefront\ChatbotController;
use App\Http\Controllers\Storefront\AddressController;
use App\Http\Controllers\Storefront\BecomeSellerController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\ConversationController;
use App\Http\Controllers\Storefront\DashboardController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\OrderController;
use App\Http\Controllers\Storefront\PrivateFileController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\ProductListController;
use App\Http\Controllers\Storefront\SearchController;
use App\Http\Controllers\Storefront\FavoriteController;
use App\Http\Controllers\Storefront\ShopController;
use App\Http\Middleware\RequireTwoFactor;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Locale switch
Route::post('/locale/{locale}', function (string $locale) {
    if (! in_array($locale, ['en', 'km', 'zh'], true)) {
        abort(400);
    }

    session(['locale' => $locale]);

    if (auth()->check()) {
        auth()->user()->update(['locale' => $locale]);
    }

    return back();
})->name('locale.switch');

// Storefront (public)
Route::get('/', HomeController::class)->name('home');
Route::get('/about',   fn () => Inertia::render('storefront/about'))->name('about');
Route::get('/contact', fn () => Inertia::render('storefront/contact'))->name('contact');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');
Route::get('/products', ProductListController::class)->name('products.index');
Route::get('/search', SearchController::class)->name('search');
Route::get('/shops', [ShopController::class, 'index'])->name('shops.index');
Route::get('/shops/{slug}', [ShopController::class, 'show'])->name('shops.show');

// Chatbot (public, rate-limited)
Route::post('/chatbot/message', [ChatbotController::class, 'message'])
    ->name('chatbot.message')
    ->middleware('throttle:20,1');

// Cart (guest + auth)
Route::prefix('cart')->name('cart.')->group(function (): void {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/', [CartController::class, 'store'])->name('store');
    Route::delete('/{item}', [CartController::class, 'destroy'])->name('destroy');
});

// Two-factor authentication (auth required, but NOT 2FA middleware)
Route::middleware('auth')->prefix('two-factor')->name('two-factor.')->group(function (): void {
    Route::get('/setup', [TwoFactorController::class, 'getQr'])->name('setup');
    Route::post('/enable', [TwoFactorController::class, 'enable'])->name('enable');
    Route::post('/confirm', [TwoFactorController::class, 'confirm'])->name('confirm');
    Route::get('/challenge', [TwoFactorController::class, 'challenge'])->name('challenge');
    Route::post('/challenge', [TwoFactorController::class, 'verify'])->name('verify');
    Route::delete('/disable', [TwoFactorController::class, 'disable'])->name('disable');
});

// Signed private file downloads (validated by Laravel signature, no extra auth needed at this point)
Route::get('/files/invoice/{order}/download', [PrivateFileController::class, 'downloadInvoice'])
    ->name('files.invoice.download')
    ->middleware('signed');
Route::get('/files/kyc/{verification}/{field}/download', [PrivateFileController::class, 'downloadKyc'])
    ->name('files.kyc.download')
    ->middleware('signed');

// Auth-required storefront routes
Route::middleware(['auth', 'verified', RequireTwoFactor::class])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('orders')->name('orders.')->group(function (): void {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::post('/{order}/cancel', [OrderController::class, 'cancel'])->name('cancel');
    });

    Route::get('/become-seller', [BecomeSellerController::class, 'index'])->name('become-seller');
    Route::post('/become-seller', [BecomeSellerController::class, 'store'])->name('become-seller.store');

    Route::prefix('checkout')->name('checkout.')->group(function (): void {
        Route::get('/', [CheckoutController::class, 'index'])->name('index');
        Route::post('/', [CheckoutController::class, 'store'])->name('store');
        Route::get('/poll/{transaction}', [CheckoutController::class, 'poll'])->name('poll');
    });

    Route::prefix('addresses')->name('addresses.')->group(function (): void {
        Route::get('/', [AddressController::class, 'index'])->name('index');
        Route::post('/', [AddressController::class, 'store'])->name('store');
        Route::put('/{address}', [AddressController::class, 'update'])->name('update');
        Route::delete('/{address}', [AddressController::class, 'destroy'])->name('destroy');
        Route::patch('/{address}/default', [AddressController::class, 'setDefault'])->name('set-default');
    });

    // Private file signed URL issuers (require auth to get the signed URL)
    Route::get('/files/invoice/{order}', [PrivateFileController::class, 'invoice'])->name('files.invoice');
    Route::get('/files/kyc/{verification}/{field}', [PrivateFileController::class, 'kycDocument'])->name('files.kyc');

    // Favorites / wishlist
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favorites/{product}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    // Chat / conversations
    Route::prefix('conversations')->name('conversations.')->group(function (): void {
        Route::get('/', [ConversationController::class, 'index'])->name('index');
        Route::post('/', [ConversationController::class, 'store'])->name('store');
        Route::get('/shop/{shopId}', [ConversationController::class, 'startWithShop'])->name('shop');
        Route::get('/api/shop/{shopId}', [ConversationController::class, 'apiWithShop'])->name('api.shop');
        Route::get('/{conversation}', [ConversationController::class, 'show'])->name('show');
        Route::post('/{conversation}/messages', [ConversationController::class, 'sendMessage'])->name('messages.store');
    });
});

// Vendor panel — product export & import template (auth required)
Route::middleware(['auth'])->prefix('vendor-panel')->name('vendor.')->group(function (): void {
    Route::get('/products/export',          [ProductExportController::class, 'export'])  ->name('products.export');
    Route::get('/products/import-template', [ProductExportController::class, 'template'])->name('products.import-template');
});

require __DIR__.'/auth.php';
