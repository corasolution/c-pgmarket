<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Shops\Tables;

use App\Actions\Shop\ApproveShop;
use App\Actions\Shop\SuspendShop;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

final class ShopsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('owner.name')->label('Owner')->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'suspended', 'rejected' => 'danger',
                        'submitted' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('commission_percent')->suffix('%')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => in_array($record->status, ['submitted', 'draft'], true))
                    ->action(function ($record): void {
                        app(ApproveShop::class)($record, auth()->user());
                    }),
                Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->form([
                        Textarea::make('reason')
                            ->label('Suspension Reason')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->visible(fn ($record): bool => $record->status === 'active')
                    ->action(function ($record, array $data): void {
                        app(SuspendShop::class)($record, auth()->user(), $data['reason']);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
