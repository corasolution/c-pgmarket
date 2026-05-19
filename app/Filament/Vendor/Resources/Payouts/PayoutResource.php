<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\Payouts;

use App\Filament\Vendor\Resources\Payouts\Pages\CreatePayout;
use App\Filament\Vendor\Resources\Payouts\Pages\EditPayout;
use App\Filament\Vendor\Resources\Payouts\Pages\ListPayouts;
use App\Filament\Vendor\Resources\Payouts\Schemas\PayoutForm;
use App\Filament\Vendor\Resources\Payouts\Tables\PayoutsTable;
use App\Models\Payout;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class PayoutResource extends Resource
{
    protected static ?string $model = Payout::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;
    protected static ?string $navigationLabel = 'Payouts';
    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string { return 'Finance'; }

    public static function form(Schema $schema): Schema
    {
        return PayoutForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PayoutsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayouts::route('/'),
            'create' => CreatePayout::route('/create'),
            'edit' => EditPayout::route('/{record}/edit'),
        ];
    }
}
