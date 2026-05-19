<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;

final class DisableTwoFactor
{
    public function __invoke(User $user): void
    {
        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }
}
