<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Models\SiteSetting;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;

final class Settings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;
    protected static ?string $navigationLabel = 'Settings';
    protected static ?int $navigationSort = 99;
    protected string $view = 'filament.admin.pages.settings';

    public static function getNavigationGroup(): string
    {
        return 'System';
    }

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $this->data = [
            'GOOGLE_CLIENT_ID'     => env('GOOGLE_CLIENT_ID', ''),
            'GOOGLE_CLIENT_SECRET' => env('GOOGLE_CLIENT_SECRET', ''),
            'GOOGLE_REDIRECT_URI'  => env('GOOGLE_REDIRECT_URI', url('/auth/google/callback')),
            'APP_NAME'             => env('APP_NAME', ''),
            'APP_URL'              => env('APP_URL', ''),
            'MAIL_FROM_NAME'       => env('MAIL_FROM_NAME', ''),
            'MAIL_FROM_ADDRESS'    => env('MAIL_FROM_ADDRESS', ''),
            'MAIL_HOST'            => env('MAIL_HOST', ''),
            'MAIL_PORT'            => env('MAIL_PORT', '587'),
            'MAIL_USERNAME'        => env('MAIL_USERNAME', ''),
            'MAIL_PASSWORD'        => env('MAIL_PASSWORD', ''),
            'MAIL_ENCRYPTION'      => env('MAIL_ENCRYPTION', 'tls'),
            'TELEGRAM_BOT_TOKEN'              => env('TELEGRAM_BOT_TOKEN', ''),
            'APOLLO_BASE_URL'                 => env('APOLLO_BASE_URL', 'https://apolo-api.codingate.com'),
            'APOLLO_DEVICE_OS'               => env('APOLLO_DEVICE_OS', 'Web'),
            'APOLLO_DEVICE_ID'               => env('APOLLO_DEVICE_ID', 'pgmarket-platform-001'),
            'APOLLO_EMAIL'                    => env('APOLLO_EMAIL', ''),
            'APOLLO_PASSWORD'                 => env('APOLLO_PASSWORD', ''),
            'APOLLO_DEFAULT_SERVICE_TYPE'     => env('APOLLO_DEFAULT_SERVICE_TYPE', 'next_day'),
            'require_email_verification' => SiteSetting::get('require_email_verification', '0') === '1',
            'site_logo'            => SiteSetting::get('site_logo') ? [SiteSetting::get('site_logo')] : [],
            'site_favicon'         => SiteSetting::get('site_favicon') ? [SiteSetting::get('site_favicon')] : [],
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Site Branding')
                    ->description('Upload your site logo and favicon. The logo appears on the login page, storefront header, and footer.')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->columns(2)
                    ->schema([
                        FileUpload::make('site_logo')
                            ->label('Site Logo')
                            ->image()
                            ->directory('branding')
                            ->disk('public')
                            ->imageResizeMode('contain')
                            ->imageCropAspectRatio(null)
                            ->maxSize(2048)
                            ->helperText('Recommended: 200x200px PNG with transparent background.'),

                        FileUpload::make('site_favicon')
                            ->label('Favicon')
                            ->image()
                            ->directory('branding')
                            ->disk('public')
                            ->maxSize(512)
                            ->helperText('Recommended: 32x32px or 64x64px ICO/PNG.'),
                    ]),

                Section::make('Google OAuth — Sign in with Google')
                    ->description('Get credentials from Google Cloud Console → APIs & Services → Credentials.')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->schema([
                        TextInput::make('GOOGLE_CLIENT_ID')
                            ->label('Client ID')
                            ->placeholder('xxxxxxxxxxxx.apps.googleusercontent.com')
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('GOOGLE_CLIENT_SECRET')
                            ->label('Client Secret')
                            ->password()
                            ->revealable()
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('GOOGLE_REDIRECT_URI')
                            ->label('Redirect URI')
                            ->helperText('Must match exactly what you entered in Google Console.')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make('Application')
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->columns(2)
                    ->schema([
                        TextInput::make('APP_NAME')
                            ->label('App Name')
                            ->required(),

                        TextInput::make('APP_URL')
                            ->label('App URL')
                            ->url()
                            ->required(),
                    ]),

                Section::make('Registration')
                    ->description('Configure user registration behavior.')
                    ->icon(Heroicon::OutlinedUserPlus)
                    ->schema([
                        Toggle::make('require_email_verification')
                            ->label('Require email verification')
                            ->helperText('When enabled, new users must verify their email address before accessing protected pages. Requires SMTP to be configured below.'),
                    ]),

                Section::make('Mail / SMTP (Brevo)')
                    ->description('Configure email delivery via Brevo SMTP relay. Get credentials from Brevo → Settings → SMTP & API.')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->columns(2)
                    ->schema([
                        TextInput::make('MAIL_FROM_NAME')
                            ->label('From Name'),

                        TextInput::make('MAIL_FROM_ADDRESS')
                            ->label('From Address')
                            ->email(),

                        TextInput::make('MAIL_HOST')
                            ->label('SMTP Host')
                            ->placeholder('smtp-relay.brevo.com'),

                        TextInput::make('MAIL_PORT')
                            ->label('SMTP Port')
                            ->placeholder('587')
                            ->numeric(),

                        TextInput::make('MAIL_USERNAME')
                            ->label('SMTP Username')
                            ->placeholder('your-brevo-login@email.com')
                            ->helperText('Your Brevo account email.'),

                        TextInput::make('MAIL_PASSWORD')
                            ->label('SMTP API Key')
                            ->password()
                            ->revealable()
                            ->helperText('Your Brevo SMTP key (from Brevo → Settings → SMTP & API).'),

                        TextInput::make('MAIL_ENCRYPTION')
                            ->label('Encryption')
                            ->placeholder('tls')
                            ->helperText('Usually "tls" for port 587.'),
                    ]),

                Section::make('Telegram Bot')
                    ->description('Vendors receive instant Telegram notifications when they get new orders. Create a bot via @BotFather on Telegram and paste the token here.')
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->schema([
                        TextInput::make('TELEGRAM_BOT_TOKEN')
                            ->label('Bot Token')
                            ->password()
                            ->revealable()
                            ->placeholder('123456789:ABCdefGhIjKlMnOpQrStUvWxYz')
                            ->helperText('Get this from @BotFather on Telegram. Vendors set their Chat ID in Shop Settings.'),
                    ]),

                Section::make('Apollo eDelivery')
                    ->description('Configure Apollo eDelivery API for automatic booking creation after successful payment. Credentials are provided by Apollo. Vendors must also set their sender province in Shop Settings.')
                    ->icon(Heroicon::OutlinedTruck)
                    ->columns(2)
                    ->schema([
                        TextInput::make('APOLLO_BASE_URL')
                            ->label('Base URL')
                            ->url()
                            ->placeholder('https://apolo-api.codingate.com')
                            ->helperText('UAT: https://apolo-api.codingate.com — Production: https://api.apolloelogistics.com')
                            ->columnSpanFull(),

                        TextInput::make('APOLLO_DEVICE_OS')
                            ->label('Device OS')
                            ->placeholder('Web'),

                        TextInput::make('APOLLO_DEVICE_ID')
                            ->label('Device ID')
                            ->placeholder('pgmarket-platform-001')
                            ->helperText('A unique static identifier for this platform.'),

                        TextInput::make('APOLLO_EMAIL')
                            ->label('Apollo Account Email')
                            ->email()
                            ->placeholder('provided by Apollo'),

                        TextInput::make('APOLLO_PASSWORD')
                            ->label('Apollo Account Password')
                            ->password()
                            ->revealable()
                            ->placeholder('provided by Apollo'),

                        Select::make('APOLLO_DEFAULT_SERVICE_TYPE')
                            ->label('Default Service Type')
                            ->options([
                                'same_day' => 'Same Day',
                                'next_day' => 'Next Day',
                                'express'  => 'Express',
                            ])
                            ->default('next_day')
                            ->helperText('Default delivery speed for all bookings.'),
                    ]),
            ]);
    }

    public function save(): void
    {
        // Save file uploads to site_settings table
        $logoValue = $this->data['site_logo'] ?? [];
        $faviconValue = $this->data['site_favicon'] ?? [];

        $logoPath = is_array($logoValue) ? ($logoValue[0] ?? null) : $logoValue;
        $faviconPath = is_array($faviconValue) ? ($faviconValue[0] ?? null) : $faviconValue;

        SiteSetting::set('site_logo', $logoPath);
        SiteSetting::set('site_favicon', $faviconPath);

        // Save registration toggle to site_settings
        SiteSetting::set(
            'require_email_verification',
            ($this->data['require_email_verification'] ?? false) ? '1' : '0',
        );

        // Auto-set MAIL_MAILER to smtp when SMTP host is configured
        $mailHost = (string) ($this->data['MAIL_HOST'] ?? '');
        if ($mailHost !== '' && $mailHost !== '127.0.0.1') {
            $this->writeEnvValue('MAIL_MAILER', 'smtp');
        }

        // Save env values
        $envKeys = [
            'GOOGLE_CLIENT_ID', 'GOOGLE_CLIENT_SECRET', 'GOOGLE_REDIRECT_URI',
            'APP_NAME', 'APP_URL',
            'MAIL_FROM_NAME', 'MAIL_FROM_ADDRESS',
            'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_ENCRYPTION',
            'TELEGRAM_BOT_TOKEN',
            'APOLLO_BASE_URL', 'APOLLO_DEVICE_OS', 'APOLLO_DEVICE_ID',
            'APOLLO_EMAIL', 'APOLLO_PASSWORD', 'APOLLO_DEFAULT_SERVICE_TYPE',
        ];

        foreach ($envKeys as $key) {
            if (isset($this->data[$key])) {
                $this->writeEnvValue($key, (string) $this->data[$key]);
            }
        }

        Artisan::call('config:clear');

        Notification::make()
            ->title('Settings saved')
            ->body('Configuration updated and cache cleared.')
            ->success()
            ->send();
    }

    private function writeEnvValue(string $key, string $value): void
    {
        $envPath = base_path('.env');
        $content = (string) file_get_contents($envPath);

        if (preg_match('/\s/', $value) || str_contains($value, '#')) {
            $value = '"' . addslashes($value) . '"';
        }

        if (preg_match("/^{$key}=.*/m", $content)) {
            $content = (string) preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
        } else {
            $content .= PHP_EOL . "{$key}={$value}";
        }

        file_put_contents($envPath, $content);
    }
}
