<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\Orders\Tables;

use App\Actions\Order\UpdateSubOrderStatus;
use App\Filament\Vendor\Resources\Orders\OrderResource;
use App\Models\SubOrder;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.reference')->label('Order Ref')->searchable()->copyable(),
                TextColumn::make('order.buyer.name')->label('Buyer')->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed', 'delivered' => 'success',
                        'cancelled', 'refunded', 'disputed' => 'danger',
                        'pending', 'paid' => 'gray',
                        default => 'info',
                    }),
                TextColumn::make('subtotal_cents')
                    ->label('Subtotal')
                    ->formatStateUsing(fn (int $state): string => '$'.number_format($state / 100, 2)),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([])
            ->recordUrl(fn (SubOrder $record): string => OrderResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                Action::make('accept')
                    ->label('Accept')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => $record->status === 'pending')
                    ->action(fn ($record) => app(UpdateSubOrderStatus::class)(
                        actor: auth()->user(),
                        subOrder: $record,
                        status: 'accepted',
                    )),
                Action::make('pack')
                    ->label('Mark Packed')
                    ->icon('heroicon-o-archive-box')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => $record->status === 'accepted')
                    ->action(fn ($record) => app(UpdateSubOrderStatus::class)(
                        actor: auth()->user(),
                        subOrder: $record,
                        status: 'packed',
                    )),
                Action::make('picked_up')
                    ->label('Picked Up')
                    ->icon('heroicon-o-truck')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => $record->status === 'packed')
                    ->action(fn ($record) => app(UpdateSubOrderStatus::class)(
                        actor: auth()->user(),
                        subOrder: $record,
                        status: 'picked_up',
                    )),
            ]);
    }
}
