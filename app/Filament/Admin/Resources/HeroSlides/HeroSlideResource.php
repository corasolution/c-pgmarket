<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\HeroSlides;

use App\Filament\Admin\Resources\HeroSlides\Pages\CreateHeroSlide;
use App\Filament\Admin\Resources\HeroSlides\Pages\EditHeroSlide;
use App\Filament\Admin\Resources\HeroSlides\Pages\ListHeroSlides;
use App\Models\HeroSlide;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class HeroSlideResource extends Resource
{
    protected static ?string $model = HeroSlide::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $navigationLabel = 'Hero Slides';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string { return 'Content'; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Slide Content')->schema([
                TextInput::make('badge')
                    ->label('Badge text (emoji + label)')
                    ->placeholder('⚡ New Arrivals')
                    ->maxLength(100),

                TextInput::make('title')
                    ->label('Headline')
                    ->required()
                    ->placeholder('Latest Tech &')
                    ->maxLength(100),

                TextInput::make('accent')
                    ->label('Accent word (colored highlight)')
                    ->placeholder('Electronics')
                    ->maxLength(100),

                Textarea::make('description')
                    ->label('Subtitle / Description')
                    ->rows(2)
                    ->maxLength(255),
            ])->columns(2),

            Section::make('Buttons')->schema([
                TextInput::make('primary_button_label')
                    ->label('Primary button label')
                    ->placeholder('Shop Now')
                    ->maxLength(60),

                TextInput::make('primary_button_url')
                    ->label('Primary button URL')
                    ->placeholder('/categories/electronics')
                    ->maxLength(255),

                TextInput::make('secondary_button_label')
                    ->label('Secondary button label')
                    ->placeholder('All Categories')
                    ->maxLength(60),

                TextInput::make('secondary_button_url')
                    ->label('Secondary button URL')
                    ->placeholder('/products')
                    ->maxLength(255),
            ])->columns(2),

            Section::make('Display Settings')->schema([
                TextInput::make('gradient')
                    ->label('Background gradient (Tailwind classes)')
                    ->placeholder('from-blue-900 via-blue-700 to-primary/80')
                    ->helperText('Tailwind from-*/via-*/to-* classes for the slide background')
                    ->maxLength(255),

                TextInput::make('sort_order')
                    ->label('Sort order')
                    ->numeric()
                    ->default(0),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width(50),

                TextColumn::make('badge')
                    ->label('Badge')
                    ->limit(30),

                TextColumn::make('title')
                    ->label('Headline')
                    ->description(fn (HeroSlide $r) => $r->accent)
                    ->searchable(),

                TextColumn::make('primary_button_label')
                    ->label('Primary CTA'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListHeroSlides::route('/'),
            'create' => CreateHeroSlide::route('/create'),
            'edit'   => EditHeroSlide::route('/{record}/edit'),
        ];
    }
}
