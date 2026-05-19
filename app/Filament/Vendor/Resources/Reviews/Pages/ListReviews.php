<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\Reviews\Pages;

use App\Filament\Vendor\Resources\Reviews\ReviewResource;
use Filament\Resources\Pages\ListRecords;

final class ListReviews extends ListRecords
{
    protected static string $resource = ReviewResource::class;
}
