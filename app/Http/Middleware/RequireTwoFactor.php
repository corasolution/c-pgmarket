<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireTwoFactor
{
    private const ROLES = ['admin', 'vendor_owner'];

    private const EXEMPT_ROUTES = [
        'two-factor.setup',
        'two-factor.enable',
        'two-factor.confirm',
        'two-factor.challenge',
        'two-factor.verify',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, self::ROLES, true)) {
            return $next($request);
        }

        $routeName = $request->route()?->getName() ?? '';
        if (in_array($routeName, self::EXEMPT_ROUTES, true)) {
            return $next($request);
        }

        // Force setup if 2FA not yet confirmed
        if (! $user->two_factor_confirmed_at) {
            return redirect()->route('two-factor.setup');
        }

        // Require challenge verification each session
        if (! $request->session()->get('two_factor_verified')) {
            return redirect()->route('two-factor.challenge');
        }

        return $next($request);
    }
}
