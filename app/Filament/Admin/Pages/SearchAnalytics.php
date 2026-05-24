<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Models\SearchQuery;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class SearchAnalytics extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;
    protected static ?string $navigationLabel = 'Search Analytics';
    protected static ?int $navigationSort = 98;
    protected string $view = 'filament.admin.pages.search-analytics';

    public static function getNavigationGroup(): string
    {
        return 'System';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SearchQuery::query()
                    ->select(
                        'query_text',
                        DB::raw('COUNT(*) as search_count'),
                        DB::raw('ROUND(AVG(results_count)) as avg_results'),
                        DB::raw('MAX(created_at) as last_searched'),
                    )
                    ->groupBy('query_text')
                    ->orderByDesc('search_count')
            )
            ->columns([
                TextColumn::make('query_text')
                    ->label('Search Term')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('search_count')
                    ->label('Times Searched')
                    ->sortable(),
                TextColumn::make('avg_results')
                    ->label('Avg Results')
                    ->sortable()
                    ->color(fn (mixed $state): string => (int) $state === 0 ? 'danger' : 'gray'),
                TextColumn::make('last_searched')
                    ->label('Last Searched')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('search_count', 'desc');
    }
}
