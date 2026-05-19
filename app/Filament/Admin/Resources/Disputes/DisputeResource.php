<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Disputes;

use App\Filament\Admin\Resources\Disputes\Pages\ListDisputes;
use App\Filament\Admin\Resources\Disputes\Pages\ViewDispute;
use App\Models\Dispute;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class DisputeResource extends Resource
{
    protected static ?string $model = Dispute::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    public static function getNavigationGroup(): ?string
    {
        return 'Operations';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('orderItem.subOrder.order.reference')->label('Order')->searchable(),
                TextColumn::make('buyer.name')->label('Buyer')->searchable(),
                TextColumn::make('shop.name')->label('Shop')->searchable(),
                TextColumn::make('reason')->limit(40),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open'      => 'danger',
                        'in_review' => 'warning',
                        'resolved'  => 'success',
                        'closed'    => 'gray',
                        default     => 'gray',
                    }),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open'      => 'Open',
                        'in_review' => 'In Review',
                        'resolved'  => 'Resolved',
                        'closed'    => 'Closed',
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDisputes::route('/'),
            'view'  => ViewDispute::route('/{record}'),
        ];
    }
}
