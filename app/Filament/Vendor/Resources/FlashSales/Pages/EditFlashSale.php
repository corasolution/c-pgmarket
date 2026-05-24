<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\FlashSales\Pages;

use App\Filament\Vendor\Resources\FlashSales\FlashSaleResource;
use Filament\Resources\Pages\EditRecord;

final class EditFlashSale extends EditRecord
{
    protected static string $resource = FlashSaleResource::class;
}
