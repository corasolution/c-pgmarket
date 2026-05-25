<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Chatbot;

use App\Filament\Admin\Resources\Chatbot\Pages\EditChatbotSetting;
use App\Filament\Admin\Resources\Chatbot\Pages\ListChatbotSettings;
use App\Models\ChatbotSetting;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class ChatbotSettingResource extends Resource
{
    protected static ?string $model = ChatbotSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string
    {
        return 'Chatbot';
    }

    public static function getNavigationUrl(): string
    {
        $setting = ChatbotSetting::first();

        if ($setting === null) {
            $setting = ChatbotSetting::create([
                'is_enabled'   => false,
                'provider'     => 'claude',
                'claude_model' => 'claude-haiku-4-5-20251001',
                'max_tokens'   => 1024,
            ]);
        }

        return static::getUrl('edit', ['record' => $setting->getKey()]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Toggle::make('is_enabled')
                ->label('Enable Chatbot')
                ->helperText('When disabled the widget is hidden from all visitors.')
                ->default(true)
                ->columnSpanFull(),

            Select::make('provider')
                ->label('AI Provider')
                ->options([
                    'claude' => 'Anthropic Claude',
                    'gemini' => 'Google Gemini (Free)',
                ])
                ->required()
                ->default('claude')
                ->live()
                ->helperText('Select which AI provider powers the chatbot.')
                ->columnSpanFull(),

            // ── Claude fields (visible when provider = claude) ──
            Select::make('claude_model')
                ->label('Claude Model')
                ->options([
                    'claude-3-5-haiku-20241022'  => 'Claude 3.5 Haiku — Fast & cheap (recommended)',
                    'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet — Balanced',
                    'claude-3-7-sonnet-20250219' => 'Claude 3.7 Sonnet — Most capable',
                    'claude-3-haiku-20240307'    => 'Claude 3 Haiku — Legacy fast',
                    'claude-3-opus-20240229'     => 'Claude 3 Opus — Legacy powerful',
                ])
                ->default('claude-3-5-haiku-20241022')
                ->visible(fn (callable $get): bool => ($get('provider') ?? 'claude') === 'claude'),

            TextInput::make('claude_api_key')
                ->label('Claude API Key')
                ->password()
                ->revealable()
                ->helperText('Get your key from console.anthropic.com. Leave blank to keep existing key.')
                ->dehydrated(fn ($state): bool => filled($state))
                ->maxLength(255)
                ->visible(fn (callable $get): bool => ($get('provider') ?? 'claude') === 'claude')
                ->columnSpanFull(),

            // ── Gemini fields (visible when provider = gemini) ──
            Select::make('gemini_model')
                ->label('Gemini Model')
                ->options([
                    'gemini-2.0-flash'      => 'Gemini 2.0 Flash — Free, fast (recommended)',
                    'gemini-2.0-flash-lite' => 'Gemini 2.0 Flash Lite — Free, fastest',
                    'gemini-1.5-flash'      => 'Gemini 1.5 Flash — Free, balanced',
                    'gemini-1.5-pro'        => 'Gemini 1.5 Pro — Free, most capable',
                ])
                ->default('gemini-2.0-flash')
                ->visible(fn (callable $get): bool => ($get('provider') ?? 'claude') === 'gemini'),

            TextInput::make('gemini_api_key')
                ->label('Gemini API Key')
                ->password()
                ->revealable()
                ->helperText('Get your free key from aistudio.google.com. Leave blank to keep existing key.')
                ->dehydrated(fn ($state): bool => filled($state))
                ->maxLength(255)
                ->visible(fn (callable $get): bool => ($get('provider') ?? 'claude') === 'gemini')
                ->columnSpanFull(),

            // ── Shared fields ──
            TextInput::make('max_tokens')
                ->label('Max Tokens per Reply')
                ->numeric()
                ->minValue(256)
                ->maxValue(4096)
                ->default(1024),

            Textarea::make('system_prompt')
                ->label('System Prompt (optional)')
                ->helperText('Override the default assistant persona and instructions. Works with both providers.')
                ->rows(5)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChatbotSettings::route('/'),
            'edit'  => EditChatbotSetting::route('/{record}/edit'),
        ];
    }
}
