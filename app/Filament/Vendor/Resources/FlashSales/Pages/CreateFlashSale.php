<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\FlashSales\Pages;

use App\Filament\Vendor\Resources\FlashSales\FlashSaleResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateFlashSale extends CreateRecord
{
    protected static string $resource = FlashSaleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $shop = auth()->user()?->ownedShop;
        $data['shop_id'] = $shop?->id;
        $data['status'] = 'scheduled';

        return $data;
    }
}
