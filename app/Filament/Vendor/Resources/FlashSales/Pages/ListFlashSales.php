<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\FlashSales\Pages;

use App\Filament\Vendor\Resources\FlashSales\FlashSaleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListFlashSales extends ListRecords
{
    protected static string $resource = FlashSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
