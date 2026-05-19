<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ConfirmTwoFactor;
use App\Actions\Auth\DisableTwoFactor;
use App\Actions\Auth\EnableTwoFactor;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PragmaRX\Google2FA\Google2FA;

final class TwoFactorController extends Controller
{
    public function setup(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->two_factor_confirmed_at) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('auth/two-factor-setup', [
            'hasSecret' => (bool) $user->two_factor_secret,
        ]);
    }

    public function enable(Request $request, EnableTwoFactor $enable): RedirectResponse
    {
        $data = $enable($request->user());

        session(['two_factor_qr_url' => $data['qr_url'], 'two_factor_secret' => $data['secret']]);

        return back();
    }

    public function getQr(Request $request): Response
    {
        $user = $request->user();

        $qrUrl = session('two_factor_qr_url');
        $secret = session('two_factor_secret');

        if (! $qrUrl && $user->two_factor_secret) {
            $google2fa = app(Google2FA::class);
            $decryptedSecret = decrypt((string) $user->two_factor_secret);
            $qrUrl = $google2fa->getQRCodeUrl(config('app.name'), $user->email, $decryptedSecret);
            $secret = $decryptedSecret;
        }

        return Inertia::render('auth/two-factor-setup', [
            'hasSecret' => (bool) $user->two_factor_secret,
            'qrUrl' => $qrUrl,
            'secret' => $secret,
        ]);
    }

    public function confirm(Request $request, ConfirmTwoFactor $confirm): RedirectResponse
    {
        $request->validate(['code' => 'required|string|size:6']);

        if (! $confirm($request->user(), $request->input('code'))) {
            return back()->withErrors(['code' => 'Invalid verification code. Please try again.']);
        }

        $request->session()->put('two_factor_verified', true);
        session()->forget(['two_factor_qr_url', 'two_factor_secret']);

        return redirect()->route('dashboard')->with('status', 'Two-factor authentication enabled.');
    }

    public function challenge(): Response
    {
        return Inertia::render('auth/two-factor-challenge');
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => 'required|string']);

        $user = $request->user();
        $secret = decrypt((string) $user->two_factor_secret);
        $google2fa = app(Google2FA::class);

        // Accept either TOTP code or a recovery code
        $validTotp = (bool) $google2fa->verifyKey($secret, $request->input('code'));
        $validRecovery = $this->useRecoveryCode($user, $request->input('code'));

        if (! $validTotp && ! $validRecovery) {
            return back()->withErrors(['code' => 'Invalid authentication code.']);
        }

        $request->session()->put('two_factor_verified', true);

        return redirect()->intended(route('dashboard'));
    }

    public function disable(Request $request, DisableTwoFactor $disable): RedirectResponse
    {
        $request->validate(['password' => 'required|current_password']);

        $disable($request->user());

        return back()->with('status', 'Two-factor authentication disabled.');
    }

    private function useRecoveryCode(mixed $user, string $code): bool
    {
        if (! $user->two_factor_recovery_codes) {
            return false;
        }

        $codes = json_decode(decrypt((string) $user->two_factor_recovery_codes), true);
        $index = array_search($code, $codes, true);

        if ($index === false) {
            return false;
        }

        // Invalidate the used recovery code
        array_splice($codes, (int) $index, 1);
        $user->update(['two_factor_recovery_codes' => encrypt(json_encode($codes))]);

        return true;
    }
}
