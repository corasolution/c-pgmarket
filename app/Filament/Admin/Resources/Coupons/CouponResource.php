<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Coupons;

use App\Models\Coupon;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string { return 'Commerce'; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')
                ->label('Coupon Code')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(50)
                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                ->helperText('Buyers enter this code at checkout.'),

            Select::make('type')
                ->label('Discount Type')
                ->options([
                    'percent'       => 'Percentage Off',
                    'fixed'         => 'Fixed Amount Off',
                    'free_shipping' => 'Free Shipping',
                ])
                ->required()
                ->live(),

            TextInput::make('value_percent')
                ->label('Discount %')
                ->numeric()
                ->minValue(1)
                ->maxValue(100)
                ->suffix('%')
                ->visible(fn (callable $get): bool => $get('type') === 'percent')
                ->required(fn (callable $get): bool => $get('type') === 'percent'),

            TextInput::make('value_cents')
                ->label('Discount Amount (cents)')
                ->numeric()
                ->minValue(1)
                ->helperText('e.g. $5.00 = 500')
                ->visible(fn (callable $get): bool => $get('type') === 'fixed')
                ->required(fn (callable $get): bool => $get('type') === 'fixed'),

            TextInput::make('min_order_cents')
                ->label('Min Order (cents)')
                ->numeric()
                ->default(0)
                ->helperText('Minimum order amount. 0 = no minimum.'),

            TextInput::make('max_discount_cents')
                ->label('Max Discount (cents)')
                ->numeric()
                ->nullable()
                ->helperText('Cap for percent discounts. Leave empty for no cap.')
                ->visible(fn (callable $get): bool => $get('type') === 'percent'),

            Select::make('shop_id')
                ->label('Shop (optional)')
                ->relationship('shop', 'name')
                ->searchable()
                ->preload()
                ->nullable()
                ->helperText('Leave empty for platform-wide coupon.'),

            TextInput::make('max_uses')
                ->label('Total Usage Limit')
                ->numeric()
                ->nullable()
                ->helperText('Leave empty for unlimited.'),

            TextInput::make('max_uses_per_user')
                ->label('Uses Per User')
                ->numeric()
                ->default(1),

            DateTimePicker::make('starts_at')
                ->label('Valid From'),

            DateTimePicker::make('expires_at')
                ->label('Expires At'),

            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable()->sortable()->copyable(),
                TextColumn::make('type')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'percent'       => 'success',
                        'fixed'         => 'info',
                        'free_shipping' => 'warning',
                        default         => 'gray',
                    }),
                TextColumn::make('times_used')
                    ->label('Used')
                    ->suffix(fn (Coupon $record): string => $record->max_uses ? " / {$record->max_uses}" : ''),
                TextColumn::make('expires_at')->dateTime()->sortable(),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit'   => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
