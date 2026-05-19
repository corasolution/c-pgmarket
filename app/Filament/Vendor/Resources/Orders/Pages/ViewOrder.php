<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\Orders\Pages;

use App\Actions\Order\UpdateSubOrderStatus;
use App\Filament\Vendor\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

final class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected string $view = 'filament.vendor.orders.view-order';

    public function getTitle(): string
    {
        return 'Order #' . $this->getRecord()->order->reference;
    }

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();

        return [
            Action::make('accept')
                ->label('Accept Order')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Accept this order?')
                ->modalDescription('Confirming will move the order to "Accepted" status.')
                ->visible(fn (): bool => $record->status === 'pending')
                ->action(function () use ($record): void {
                    $this->transitionStatus($record, 'accepted');
                }),

            Action::make('pack')
                ->label('Mark as Packed')
                ->icon('heroicon-o-archive-box')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Mark as packed?')
                ->modalDescription('This means the items are packaged and ready for pickup.')
                ->visible(fn (): bool => $record->status === 'accepted')
                ->action(function () use ($record): void {
                    $this->transitionStatus($record, 'packed');
                }),

            Action::make('picked_up')
                ->label('Mark as Picked Up')
                ->icon('heroicon-o-truck')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Mark as picked up?')
                ->modalDescription('The delivery courier has collected the package.')
                ->visible(fn (): bool => $record->status === 'packed')
                ->action(function () use ($record): void {
                    $this->transitionStatus($record, 'picked_up');
                }),

            Action::make('back')
                ->label('Back to Orders')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(OrderResource::getUrl('index')),
        ];
    }

    private function transitionStatus(mixed $record, string $status): void
    {
        app(UpdateSubOrderStatus::class)(
            actor: auth()->user(),
            subOrder: $record,
            status: $status,
        );

        Notification::make()
            ->title('Order updated to "' . str_replace('_', ' ', $status) . '"')
            ->success()
            ->send();

        $this->redirect(OrderResource::getUrl('view', ['record' => $record]));
    }
}
