<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\Payouts\Pages;

use App\Actions\Payout\RequestPayout;
use App\Filament\Vendor\Resources\Payouts\PayoutResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreatePayout extends CreateRecord
{
    protected static string $resource = PayoutResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $user = auth()->user();
        $shop = $user->ownedShop;

        if ($shop === null) {
            Notification::make()->title('No shop found')->danger()->send();
            $this->halt();
        }

        return app(RequestPayout::class)(
            user: $user,
            shop: $shop,
            amountCents: (int) $data['amount_cents'],
            bankName: $data['bank_name'],
            bankAccountNumber: $data['bank_account_number'],
            bankAccountName: $data['bank_account_name'],
        );
    }
}
