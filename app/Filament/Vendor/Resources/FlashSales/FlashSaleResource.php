<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\FlashSales;

use App\Models\FlashSale;
use App\Models\ProductVariant;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class FlashSaleResource extends Resource
{
    protected static ?string $model = FlashSale::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static ?string $navigationLabel = 'Flash Sales';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string { return 'Shop'; }

    public static function form(Schema $schema): Schema
    {
        $shopId = auth()->user()?->ownedShop?->id;

        return $schema->components([
            Select::make('product_variant_id')
                ->label('Product Variant')
                ->options(function () use ($shopId): array {
                    if ($shopId === null) {
                        return [];
                    }

                    return ProductVariant::query()
                        ->whereHas('product', fn ($q) => $q->where('shop_id', $shopId)->where('status', 'active'))
                        ->with('product')
                        ->get()
                        ->mapWithKeys(fn (ProductVariant $v) => [
                            $v->id => ($v->product->name_i18n['en'] ?? $v->sku) . ' — ' . $v->sku . ' ($' . number_format($v->price_cents / 100, 2) . ')',
                        ])
                        ->all();
                })
                ->searchable()
                ->required(),

            TextInput::make('sale_price_cents')
                ->label('Sale Price (cents)')
                ->helperText('e.g. $8.00 = 800')
                ->numeric()
                ->required()
                ->minValue(1),

            TextInput::make('quantity_limit')
                ->label('Quantity Limit')
                ->numeric()
                ->nullable()
                ->helperText('Max units at sale price. Leave empty for unlimited.'),

            DateTimePicker::make('starts_at')
                ->label('Start Time')
                ->required(),

            DateTimePicker::make('ends_at')
                ->label('End Time')
                ->required()
                ->after('starts_at'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('variant.product.name_i18n')
                    ->label('Product')
                    ->formatStateUsing(fn (mixed $state): string => is_array($state) ? ($state['en'] ?? $state['km'] ?? '') : (string) $state),
                TextColumn::make('variant.sku')->label('SKU'),
                TextColumn::make('sale_price_cents')
                    ->label('Sale Price')
                    ->formatStateUsing(fn (int $state): string => '$' . number_format($state / 100, 2)),
                TextColumn::make('quantity_sold')
                    ->label('Sold')
                    ->suffix(fn (FlashSale $record): string => $record->quantity_limit ? " / {$record->quantity_limit}" : ''),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active'    => 'success',
                        'scheduled' => 'info',
                        'completed' => 'gray',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    }),
                TextColumn::make('starts_at')->dateTime()->sortable(),
                TextColumn::make('ends_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFlashSales::route('/'),
            'create' => Pages\CreateFlashSale::route('/create'),
            'edit'   => Pages\EditFlashSale::route('/{record}/edit'),
        ];
    }
}
