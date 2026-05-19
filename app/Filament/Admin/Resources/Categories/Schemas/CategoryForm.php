<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name_i18n.en')
                    ->label('Name (English)')
                    ->required()
                    ->maxLength(255),
                TextInput::make('name_i18n.km')
                    ->label('Name (Khmer)')
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Select::make('parent_id')
                    ->label('Parent Category')
                    ->options(function (?Category $record): array {
                        return self::buildCategoryTree(exclude: $record?->id);
                    })
                    ->searchable()
                    ->nullable(),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private static function buildCategoryTree(?int $parentId = null, int $depth = 0, ?int $exclude = null): array
    {
        $options = [];

        $query = Category::where('is_active', true)
            ->where('parent_id', $parentId)
            ->orderBy('sort_order');

        if ($exclude !== null) {
            $query->where('id', '!=', $exclude);
        }

        foreach ($query->get() as $category) {
            $prefix = $depth > 0 ? str_repeat('— ', $depth) : '';
            $name = $category->name_i18n['en'] ?? $category->name_i18n['km'] ?? (string) $category->id;
            $options[$category->id] = $prefix.$name;

            $children = self::buildCategoryTree($category->id, $depth + 1, $exclude);
            $options += $children;
        }

        return $options;
    }
}
