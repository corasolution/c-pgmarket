<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\Products\Pages;

use App\Filament\Vendor\Resources\Products\ProductResource;
use App\Imports\ProductsImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

final class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('template')
                ->label('Download Template')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->url(fn (): string => route('vendor.products.import-template'))
                ->openUrlInNewTab(false),

            Action::make('import')
                ->label('Import')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->modalWidth('md')
                ->modalHeading('Import Products from Excel')
                ->modalDescription('Upload an .xlsx or .csv file. Use the template above to ensure correct column names. Same name_en = multiple variants for one product.')
                ->form([
                    FileUpload::make('file')
                        ->label('Excel / CSV File (.xlsx, .csv)')
                        ->disk('local')
                        ->directory('product-imports')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'text/csv',
                            'application/csv',
                            'text/plain',
                        ])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    /** @var \App\Models\User $user */
                    $user   = auth()->user();
                    $shopId = $user->ownedShop?->id ?? $user->staffShop?->id ?? 0;

                    try {
                        Excel::import(
                            new ProductsImport($shopId),
                            Storage::disk('local')->path($data['file']),
                        );

                        Notification::make()
                            ->title('Import successful')
                            ->body('Products have been imported.')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Import failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('export')
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn (): string => route('vendor.products.export'))
                ->openUrlInNewTab(false),

            CreateAction::make(),
        ];
    }
}
