<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Models\SiteSetting;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
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
