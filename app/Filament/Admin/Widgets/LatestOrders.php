<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

final class LatestOrders extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Latest Orders';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::with('buyer')
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('reference')
                    ->label('Ref')
                    ->searchable()
                    ->copyable()
                    ->weight('bold')
                    ->size('sm'),
                TextColumn::make('buyer.name')
                    ->label('Buyer')
                    ->size('sm'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed', 'delivered' => 'success',
                        'cancelled', 'refunded', 'disputed' => 'danger',
                        'pending' => 'gray',
                        'paid' => 'info',
                        default => 'warning',
                    })
                    ->size('sm'),
                TextColumn::make('total_cents')
                    ->label('Total')
                    ->formatStateUsing(fn (int $state): string => '$' . number_format($state / 100, 2))
                    ->size('sm'),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->since()
                    ->size('sm'),
            ])
            ->paginated(false);
    }
}
