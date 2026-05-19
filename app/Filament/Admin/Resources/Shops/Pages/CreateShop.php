<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Shops\Pages;

use App\Filament\Admin\Resources\Shops\ShopResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateShop extends CreateRecord
{
    protected static string $resource = ShopResource::class;
}
