<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\Payouts\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class PayoutForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('amount_cents')
                    ->label('Amount (USD cents, min $1.00)')
                    ->numeric()
                    ->required()
                    ->minValue(100),
                TextInput::make('bank_name')->label('Bank Name')->required()->maxLength(100),
                TextInput::make('bank_account_number')->label('Account Number')->required()->maxLength(50),
                TextInput::make('bank_account_name')->label('Account Holder Name')->required()->maxLength(100),
            ]);
    }
}
