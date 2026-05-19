<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\Customers\Pages;

use App\Filament\Vendor\Resources\Customers\CustomerResource;
use App\Models\ShopCustomer;
use App\Models\SubOrder;
use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ViewCustomer extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = CustomerResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer Profile')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email')->copyable(),
                        TextEntry::make('phone')->placeholder('Not provided'),
                    ]),

                Section::make('Purchase Summary')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('shop_orders_count')
                            ->label('Total Orders')
                            ->badge()
                            ->color('primary'),

                        TextEntry::make('total_spent_cents')
                            ->label('Total Spent')
                            ->formatStateUsing(fn (int $state): string => '$' . number_format($state / 100, 2)),

                        TextEntry::make('first_order_at')
                            ->label('First Order')
                            ->dateTime('M d, Y H:i'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        /** @var ShopCustomer $customer */
        $customer = $this->getRecord();
        $shopId   = $this->resolveShopId();

        return $table
            ->query(
                SubOrder::withoutGlobalScopes()
                    ->with(['order', 'items'])
                    ->where('shop_id', $shopId)
                    ->whereHas('order', fn (Builder $q) => $q->where('buyer_id', $customer->id))
            )
            ->heading('Order History')
            ->columns([
                TextColumn::make('order.reference')
                    ->label('Order #')
                    ->searchable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'    => 'warning',
                        'processing' => 'info',
                        'shipped'    => 'primary',
                        'delivered'  => 'success',
                        'cancelled'  => 'danger',
                        default      => 'gray',
                    }),

                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items'),

                TextColumn::make('subtotal_cents')
                    ->label('Subtotal')
                    ->formatStateUsing(fn (int $state): string => '$' . number_format($state / 100, 2)),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    private function resolveShopId(): int
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->ownedShop?->id ?? $user->staffShop?->id ?? 0;
    }
}
