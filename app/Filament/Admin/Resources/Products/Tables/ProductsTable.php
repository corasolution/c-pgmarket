<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Products\Tables;

use Filament\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('raw_images')
                    ->label('Image')
                    ->circular()
                    ->stacked()
                    ->limit(1)
                    ->disk('public')
                    ->width(40)
                    ->height(40),
                TextColumn::make('name_i18n.en')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                TextColumn::make('shop.name')
                    ->label('Shop')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name_i18n')
                    ->label('Category')
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state['en'] ?? '') : (string) $state),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'draft' => 'warning',
                        'archived' => 'gray',
                        default => 'info',
                    }),
                ToggleColumn::make('is_featured')
                    ->label('Featured'),
                TextColumn::make('variants_count')
                    ->counts('variants')
                    ->label('Variants')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'draft' => 'Draft',
                        'archived' => 'Archived',
                    ]),
                SelectFilter::make('shop')
                    ->relationship('shop', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => $record->status !== 'active')
                    ->action(fn ($record) => $record->update(['status' => 'active'])),
                Action::make('archive')
                    ->label('Archive')
                    ->icon('heroicon-o-archive-box')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => $record->status === 'active')
                    ->action(fn ($record) => $record->update(['status' => 'archived'])),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
