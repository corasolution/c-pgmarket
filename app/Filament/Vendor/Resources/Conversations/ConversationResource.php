<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\Conversations;

use App\Filament\Vendor\Resources\Conversations\Pages\ListConversations;
use App\Filament\Vendor\Resources\Conversations\Pages\ViewConversation;
use App\Models\Conversation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class ConversationResource extends Resource
{
    protected static ?string $model = Conversation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;
    protected static ?string $navigationLabel = 'Messages';
    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return 'Shop';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('buyer.name')
                    ->label('Buyer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('lastMessagePreview')
                    ->label('Last Message')
                    ->getStateUsing(function (Conversation $record): string {
                        $lastMessage = $record->messages()->latest()->first();
                        if (! $lastMessage) {
                            return '—';
                        }

                        return \Illuminate\Support\Str::limit($lastMessage->body, 50);
                    }),

                TextColumn::make('unread')
                    ->label('Unread')
                    ->getStateUsing(function (Conversation $record): string {
                        $shop = \App\Models\Shop::where('owner_id', auth()->id())->first();
                        if (! $shop) {
                            return '0';
                        }

                        $count = $record->messages()
                            ->whereNull('read_at')
                            ->where('sender_id', '!=', auth()->id())
                            ->count();

                        return $count > 0 ? (string) $count : '';
                    })
                    ->badge()
                    ->color('danger'),

                TextColumn::make('last_message_at')
                    ->label('Last Activity')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('last_message_at', 'desc')
            ->recordUrl(fn (Conversation $record): string => ConversationResource::getUrl('view', ['record' => $record]));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConversations::route('/'),
            'view'  => ViewConversation::route('/{record}'),
        ];
    }
}
