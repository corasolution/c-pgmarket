<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Contracts\PaymentGateway;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * ABA PayWay pushback webhook handler.
 *
 * Registered outside all middleware (no session, no CSRF, no Inertia).
 * ALWAYS returns plain-text 200 "Completed" — ABA rejects HTML error pages.
 */
final class AbaPayWayWebhookController extends Controller
{
    public function handle(Request $request, PaymentGateway $gateway): Response
    {
        try {
            $payload = $request->all();

            if (! $gateway->verifyWebhook($payload)) {
                Log::warning('ABA PayWay webhook: invalid signature', [
                    'ip' => $request->ip(),
                    'fields' => $request->except('hash'),
                ]);

                return $this->completed();
            }

            $gateway->handleCallback($payload);
        } catch (\Throwable $e) {
            Log::error('ABA PayWay webhook: unhandled exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Always return plain-text 200 — ABA rejects HTML error pages
        return $this->completed();
    }

    private function completed(): Response
    {
        return response('Completed', 200)->header('Content-Type', 'text/plain');
    }
}
