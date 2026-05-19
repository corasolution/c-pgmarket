<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\Customers\Pages;

use App\Filament\Vendor\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\ListRecords;

final class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
