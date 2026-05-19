<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Wallets;

use App\Filament\Admin\Resources\Wallets\Pages\ListWallets;
use App\Models\VendorWallet;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class WalletResource extends Resource
{
    protected static ?string $model = VendorWallet::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;
    protected static ?string $navigationLabel = 'Vendor Wallets';
    protected static ?string $modelLabel = 'Vendor Wallet';
    protected static ?string $pluralModelLabel = 'Vendor Wallets';
    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string { return 'Finance'; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('shop.name')
                    ->label('Shop')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('shop.owner.name')
                    ->label('Owner')
                    ->searchable(),
                TextColumn::make('available_balance_cents')
                    ->label('Available')
                    ->formatStateUsing(fn (int $state): string => '$' . number_format($state / 100, 2))
                    ->color('success')
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('pending_balance_cents')
                    ->label('In Escrow')
                    ->formatStateUsing(fn (int $state): string => '$' . number_format($state / 100, 2))
                    ->color('warning')
                    ->sortable(),
                TextColumn::make('lifetime_earned_cents')
                    ->label('Lifetime')
                    ->formatStateUsing(fn (int $state): string => '$' . number_format($state / 100, 2))
                    ->color('info')
                    ->sortable(),
                TextColumn::make('transactions_count')
                    ->counts('transactions')
                    ->label('Txns')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Last Activity')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('available_balance_cents', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWallets::route('/'),
        ];
    }
}
