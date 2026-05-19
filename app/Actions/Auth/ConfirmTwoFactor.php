<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

final class ConfirmTwoFactor
{
    public function __construct(private readonly Google2FA $google2fa) {}

    public function __invoke(User $user, string $code): bool
    {
        $secret = decrypt((string) $user->two_factor_secret);

        $valid = (bool) $this->google2fa->verifyKey($secret, $code);

        if ($valid) {
            $user->update([
                'two_factor_enabled' => true,
                'two_factor_confirmed_at' => now(),
                'two_factor_recovery_codes' => encrypt(json_encode($this->generateRecoveryCodes())),
            ]);
        }

        return $valid;
    }

    /** @return list<string> */
    private function generateRecoveryCodes(): array
    {
        return array_map(
            fn (): string => strtoupper(bin2hex(random_bytes(5))).'-'.strtoupper(bin2hex(random_bytes(5))),
            range(1, 8),
        );
    }
}
