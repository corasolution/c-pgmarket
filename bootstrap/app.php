<?php

use App\Http\Controllers\Webhook\AbaPayWayWebhookController;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function (): void {
            // Webhook routes — no middleware (no session, no Inertia, no CSRF)
            Route::post('/webhooks/aba-payway', [AbaPayWayWebhookController::class, 'handle'])
                ->name('webhooks.aba-payway')
                ->withoutMiddleware('*');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Webhook routes must always return plain text, never HTML error pages
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('webhooks/*')) {
                return response('Completed', 200)->header('Content-Type', 'text/plain');
            }
        });
    })->create();
