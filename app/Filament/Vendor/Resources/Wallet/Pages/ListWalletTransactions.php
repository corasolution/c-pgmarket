<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\Wallet\Pages;

use App\Filament\Vendor\Resources\Wallet\WalletResource;
use Filament\Resources\Pages\ListRecords;

final class ListWalletTransactions extends ListRecords
{
    protected static string $resource = WalletResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
