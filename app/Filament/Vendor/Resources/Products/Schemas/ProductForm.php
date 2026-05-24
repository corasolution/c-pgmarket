<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\Products\Schemas;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Str;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

final class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([

                /* ── 1. Basic Info ── */
                Section::make('Basic Information')
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name_i18n.en')
                            ->label('Name (English)')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $state, callable $set, callable $get) {
                                if (empty($get('slug'))) {
                                    $set('slug', Str::slug($state));
                                }
                            }),

                        TextInput::make('name_i18n.km')
                            ->label('Name (Khmer)')
                            ->maxLength(255),

                        TextInput::make('slug')
                            ->label('URL Slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Auto-generated from name. Edit carefully.')
                            ->columnSpanFull(),
                    ]),

                /* ── 2. Publishing (status + category + brand + featured) ── */
                Section::make('Publishing')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->columns(4)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'draft'    => 'Draft',
                                'active'   => 'Active',
                                'archived' => 'Archived',
                            ])
                            ->required()
                            ->default('draft'),

                        Select::make('category_id')
                            ->label('Category')
                            ->options(function (): array {
                                return self::buildCategoryTree();
                            })
                            ->searchable()
                            ->required(),

                        Select::make('brand_id')
                            ->label('Brand')
                            ->relationship(
                                name: 'brand',
                                titleAttribute: 'id',
                                modifyQueryUsing: fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn ($record) => $record->name_i18n['en']
                                    ?? $record->name_i18n['km']
                                    ?? $record->slug
                            )
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Optional. Pick the product\'s brand.'),

                        Toggle::make('stock_track')
                            ->label('Track Stock')
                            ->helperText('Enable to track inventory. Disable for unlimited stock.')
                            ->default(false)
                            ->live(),

                        Toggle::make('is_featured')
                            ->label('Featured Product')
                            ->helperText('Assigned by admin only.')
                            ->disabled(),
                    ]),

                /* ── 3. Description EN ── */
                Section::make('Description (English)')
                    ->icon(Heroicon::OutlinedLanguage)
                    ->columnSpanFull()
                    ->schema([
                        RichEditor::make('description_i18n.en')
                            ->label('')
                            ->toolbarButtons([
                                'bold', 'italic', 'underline', 'strike',
                                'h2', 'h3',
                                'bulletList', 'orderedList',
                                'blockquote', 'codeBlock',
                                'link', 'attachFiles',
                            ])
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('product-attachments')
                            ->columnSpanFull(),
                    ]),

                /* ── 4. Description KH (collapsed) ── */
                Section::make('Description (Khmer)')
                    ->icon(Heroicon::OutlinedLanguage)
                    ->collapsed()
                    ->columnSpanFull()
                    ->schema([
                        RichEditor::make('description_i18n.km')
                            ->label('')
                            ->toolbarButtons([
                                'bold', 'italic', 'underline', 'strike',
                                'h2', 'h3',
                                'bulletList', 'orderedList',
                                'blockquote', 'link', 'attachFiles',
                            ])
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('product-attachments')
                            ->columnSpanFull(),
                    ]),

                /* ── 5. Variants & Pricing ── */
                Section::make('Variants & Pricing')
                    ->icon(Heroicon::OutlinedTag)
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('variants')
                            ->relationship('variants')
                            ->label('')
                            ->schema([
                                Grid::make(4)->schema([
                                    TextInput::make('sku')
                                        ->label('SKU')
                                        ->required()
                                        ->maxLength(100),

                                    TextInput::make('stock_quantity')
                                        ->label('Stock')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(0)
                                        ->required(fn (callable $get): bool => (bool) $get('../../stock_track'))
                                        ->visible(fn (callable $get): bool => (bool) $get('../../stock_track')),

                                    TextInput::make('low_stock_threshold')
                                        ->label('Low Stock Alert')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(5)
                                        ->helperText('Get notified when stock drops below this.')
                                        ->visible(fn (callable $get): bool => (bool) $get('../../stock_track')),

                                    TextInput::make('price_cents')
                                        ->label('Price (cents)')
                                        ->helperText('e.g. $10.00 → 1000')
                                        ->numeric()
                                        ->required()
                                        ->minValue(0),

                                    Select::make('price_currency')
                                        ->label('Currency')
                                        ->options(['USD' => 'USD', 'KHR' => 'KHR'])
                                        ->default('USD')
                                        ->required(),

                                    KeyValue::make('options')
                                        ->label('Options (e.g. Color → Red)')
                                        ->columnSpanFull(),
                                ]),
                            ])
                            ->addActionLabel('Add variant')
                            ->minItems(1)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['sku'] ?? null),
                    ]),

                /* ── 6. Product Images ── */
                Section::make('Product Images')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->columnSpanFull()
                    ->schema([
                        FileUpload::make('images')
                            ->label('')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->disk('public')
                            ->directory('product-images')
                            ->afterStateHydrated(function (FileUpload $component, mixed $state): void {
                                if (! is_array($state)) {
                                    return;
                                }
                                $paths = array_values(array_map(function (string $item): string {
                                    if (filter_var($item, FILTER_VALIDATE_URL)) {
                                        $urlPath = (string) parse_url($item, PHP_URL_PATH);
                                        return ltrim(str_replace('/storage/', '', $urlPath), '/');
                                    }
                                    return $item;
                                }, $state));
                                $component->state($paths);
                            })
                            ->rules(['max:5120'])
                            ->maxFiles(8)
                            ->imageResizeMode('contain')
                            ->imageResizeTargetWidth('1500')
                            ->imageResizeTargetHeight('1500')
                            ->imageResizeUpscale(false)
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                null,
                                '1:1',
                                '4:3',
                                '16:9',
                            ])
                            ->imageEditorViewportWidth(1200)
                            ->imageEditorViewportHeight(800)
                            ->helperText('Upload up to 8 images (max 5 MB each). Click an image to crop or edit. Drag to reorder. Images are auto-compressed on save.'),
                    ]),
            ]);
    }

    /**
     * Build a flat options array with indented labels showing category hierarchy.
     * e.g. "Electronics", "— Smartphones", "— — iPhone"
     *
     * @return array<int, string>
     */
    private static function buildCategoryTree(?int $parentId = null, int $depth = 0): array
    {
        $options = [];

        $categories = Category::where('is_active', true)
            ->where('parent_id', $parentId)
            ->orderBy('sort_order')
            ->get();

        foreach ($categories as $category) {
            $prefix = $depth > 0 ? str_repeat('— ', $depth) : '';
            $name = $category->name_i18n['en'] ?? $category->name_i18n['km'] ?? (string) $category->id;
            $options[$category->id] = $prefix.$name;

            // Recurse into children
            $children = self::buildCategoryTree($category->id, $depth + 1);
            $options += $children;
        }

        return $options;
    }
}
