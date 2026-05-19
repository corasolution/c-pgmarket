<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Wallets\Pages;

use App\Filament\Admin\Resources\Wallets\WalletResource;
use Filament\Resources\Pages\ListRecords;

final class ListWallets extends ListRecords
{
    protected static string $resource = WalletResource::class;
}
