<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\Customers;

use App\Filament\Vendor\Resources\Customers\Pages\ListCustomers;
use App\Filament\Vendor\Resources\Customers\Pages\ViewCustomer;
use App\Models\ShopCustomer;
use App\Models\SubOrder;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class CustomerResource extends Resource
{
    protected static ?string $model = ShopCustomer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;
    protected static ?string $navigationLabel  = 'Customers';
    protected static ?string $modelLabel       = 'Customer';

    public static function getNavigationGroup(): ?string { return 'Shop'; }
    protected static ?string $pluralModelLabel = 'Customers';
    protected static ?int    $navigationSort   = 3;
    protected static bool    $shouldRegisterNavigation = true;

    public static function getEloquentQuery(): Builder
    {
        $shopId = self::resolveShopId();

        return parent::getEloquentQuery()
            ->select('users.*')
            ->addSelect([
                'shop_orders_count' => SubOrder::selectRaw('COUNT(*)')
                    ->join('orders', 'sub_orders.order_id', '=', 'orders.id')
                    ->whereColumn('orders.buyer_id', 'users.id')
                    ->where('sub_orders.shop_id', $shopId),

                'total_spent_cents' => SubOrder::selectRaw('COALESCE(SUM(subtotal_cents), 0)')
                    ->join('orders', 'sub_orders.order_id', '=', 'orders.id')
                    ->whereColumn('orders.buyer_id', 'users.id')
                    ->where('sub_orders.shop_id', $shopId),

                'first_order_at' => SubOrder::select('sub_orders.created_at')
                    ->join('orders', 'sub_orders.order_id', '=', 'orders.id')
                    ->whereColumn('orders.buyer_id', 'users.id')
                    ->where('sub_orders.shop_id', $shopId)
                    ->orderBy('sub_orders.created_at')
                    ->limit(1),

                'last_order_at' => SubOrder::select('sub_orders.created_at')
                    ->join('orders', 'sub_orders.order_id', '=', 'orders.id')
                    ->whereColumn('orders.buyer_id', 'users.id')
                    ->where('sub_orders.shop_id', $shopId)
                    ->orderByDesc('sub_orders.created_at')
                    ->limit(1),
            ])
            ->whereHas('orders', fn (Builder $q) =>
                $q->whereHas('subOrders', fn (Builder $sq) =>
                    $sq->where('shop_id', $shopId)
                )
            );
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('phone')
                    ->placeholder('—'),

                TextColumn::make('shop_orders_count')
                    ->label('Orders')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('total_spent_cents')
                    ->label('Total Spent')
                    ->formatStateUsing(fn (int $state): string => '$' . number_format($state / 100, 2))
                    ->sortable(),

                TextColumn::make('first_order_at')
                    ->label('First Order')
                    ->dateTime('M d, Y')
                    ->sortable(),

                TextColumn::make('last_order_at')
                    ->label('Last Order')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->defaultSort('last_order_at', 'desc')
            ->recordUrl(fn (User $record): string => self::getUrl('view', ['record' => $record]))
            ->searchPlaceholder('Search by name or email…');
    }

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /** @return array<string, \Filament\Resources\Pages\PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'view'  => ViewCustomer::route('/{record}'),
        ];
    }

    private static function resolveShopId(): int
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->ownedShop?->id ?? $user->staffShop?->id ?? 0;
    }
}
