<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\Wallet;

use App\Filament\Vendor\Resources\Wallet\Pages\ListWalletTransactions;
use App\Models\Shop;
use App\Models\WalletTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class WalletResource extends Resource
{
    protected static ?string $model = WalletTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
    protected static ?string $navigationLabel = 'Wallet History';
    protected static ?string $slug = 'wallet-history';
    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'Finance';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): void {
                $shop = Shop::where('owner_id', auth()->id())->first();

                if ($shop?->wallet) {
                    $query->where('vendor_wallet_id', $shop->wallet->id);
                } else {
                    $query->whereRaw('1 = 0');
                }
            })
            ->columns([
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'credit' => 'success',
                        'debit'  => 'danger',
                        default  => 'gray',
                    }),

                TextColumn::make('reason')
                    ->label('Reason')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'order_payment'  => 'info',
                        'escrow_release' => 'success',
                        'commission'     => 'warning',
                        'payout'         => 'gray',
                        'refund'         => 'danger',
                        default          => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucfirst($state))),

                TextColumn::make('amount_cents')
                    ->label('Amount')
                    ->formatStateUsing(function (int $state, WalletTransaction $record): string {
                        $formatted = '$' . number_format(abs($state) / 100, 2);

                        return $record->type === 'credit' ? '+' . $formatted : '-' . $formatted;
                    })
                    ->color(fn (WalletTransaction $record): string => $record->type === 'credit' ? 'success' : 'danger'),

                TextColumn::make('balance_after_cents')
                    ->label('Balance After')
                    ->formatStateUsing(fn (int $state): string => '$' . number_format($state / 100, 2)),

                TextColumn::make('reference')
                    ->label('Reference')
                    ->fontFamily('mono')
                    ->copyable()
                    ->placeholder('—'),

                TextColumn::make('note')
                    ->label('Note')
                    ->limit(40)
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('reason')
                    ->options([
                        'order_payment'  => 'Order Payment',
                        'escrow_release' => 'Escrow Release',
                        'commission'     => 'Commission',
                        'payout'         => 'Payout',
                        'refund'         => 'Refund',
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWalletTransactions::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
