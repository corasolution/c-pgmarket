<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Chatbot;

use App\Filament\Admin\Resources\Chatbot\Pages\CreateChatbotKnowledgeBase;
use App\Filament\Admin\Resources\Chatbot\Pages\EditChatbotKnowledgeBase;
use App\Filament\Admin\Resources\Chatbot\Pages\ListChatbotKnowledgeBases;
use App\Models\ChatbotKnowledgeBase;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class ChatbotKnowledgeBaseResource extends Resource
{
    protected static ?string $model = ChatbotKnowledgeBase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $navigationLabel = 'Knowledge Base';

    protected static ?string $modelLabel = 'Knowledge Base Entry';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string
    {
        return 'Chatbot';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Select::make('source_type')
                ->label('Source Type')
                ->options([
                    'manual' => 'Manual Text',
                    'url'    => 'Web URL (reference only)',
                ])
                ->default('manual')
                ->required()
                ->live(),

            TextInput::make('source_url')
                ->label('Source URL')
                ->url()
                ->nullable()
                ->visible(fn ($get): bool => $get('source_type') === 'url')
                ->columnSpanFull(),

            Textarea::make('content')
                ->label('Content')
                ->helperText('This text will be injected into the AI context for every conversation.')
                ->rows(8)
                ->required()
                ->columnSpanFull(),

            TextInput::make('sort_order')
                ->label('Sort Order')
                ->numeric()
                ->default(0),

            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('source_type')->label('Type')->badge(),
                TextColumn::make('content')
                    ->limit(60)
                    ->label('Content Preview'),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('sort_order')->sortable()->label('Order'),
                TextColumn::make('updated_at')->dateTime()->label('Updated')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListChatbotKnowledgeBases::route('/'),
            'create' => CreateChatbotKnowledgeBase::route('/create'),
            'edit'   => EditChatbotKnowledgeBase::route('/{record}/edit'),
        ];
    }
}
