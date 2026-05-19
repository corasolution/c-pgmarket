<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\Orders\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

final class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('status')
                    ->options([
                        'accepted' => 'Accepted',
                        'packed' => 'Packed',
                        'picked_up' => 'Picked Up',
                    ])
                    ->required(),
            ]);
    }
}
