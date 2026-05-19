<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Payouts\Tables;

use App\Actions\Payout\ApprovePayout;
use App\Models\AuditLog;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class PayoutsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('shop.name')->label('Shop')->searchable(),
                TextColumn::make('amount_cents')
                    ->label('Amount')
                    ->formatStateUsing(fn (int $state): string => '$'.number_format($state / 100, 2)),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'pending'  => 'warning',
                        default    => 'gray',
                    }),
                TextColumn::make('bank_name'),
                TextColumn::make('bank_account_number'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([])
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => $record->status === 'pending')
                    ->action(function ($record): void {
                        app(ApprovePayout::class)(auth()->user(), $record);
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->visible(fn ($record): bool => $record->status === 'pending')
                    ->action(function ($record, array $data): void {
                        $before = ['status' => $record->status];
                        $record->update([
                            'status' => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                        ]);
                        AuditLog::create([
                            'user_id' => auth()->id(),
                            'action' => 'payout.reject',
                            'auditable_type' => $record::class,
                            'auditable_id' => $record->id,
                            'before' => $before,
                            'after' => ['status' => 'rejected', 'reason' => $data['rejection_reason']],
                        ]);
                    }),
            ])
            ->toolbarActions([]);
    }
}
