<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

final class EnableTwoFactor
{
    public function __construct(private readonly Google2FA $google2fa) {}

    /**
     * Generate a new TOTP secret and store it (unconfirmed) on the user.
     *
     * @return array{secret: string, qr_url: string}
     */
    public function __invoke(User $user): array
    {
        $secret = $this->google2fa->generateSecretKey();

        $user->update([
            'two_factor_secret' => encrypt($secret),
            'two_factor_confirmed_at' => null,
        ]);

        $qrUrl = $this->google2fa->getQRCodeUrl(
            company: config('app.name'),
            holder: $user->email,
            secret: $secret,
        );

        return ['secret' => $secret, 'qr_url' => $qrUrl];
    }
}
