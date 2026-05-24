<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Pages;

use App\Models\Shop;
use App\Services\Delivery\ApolloDeliveryProvider;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

final class ShopSettings extends Page
{
    protected string $view = 'filament.vendor.pages.shop-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;
    protected static ?string $navigationLabel = 'Shop Settings';
    protected static ?int $navigationSort = 99;

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $shop = $this->getShop();

        $this->form->fill([
            'name'             => $shop?->name ?? '',
            'phone'            => $shop?->phone ?? '',
            'email'            => $shop?->email ?? '',
            'description_en'   => $shop?->description_i18n['en'] ?? '',
            'description_km'   => $shop?->description_i18n['km'] ?? '',
            'facebook_page'    => $shop?->facebook_page ?? '',
            'telegram'         => $shop?->telegram ?? '',
            'telegram_chat_id' => $shop?->telegram_chat_id ?? '',
            'logo'                 => null,
            'banner'               => null,
            'apollo_province_id'   => $shop?->apollo_province_id,
            'apollo_district_id'   => $shop?->apollo_district_id,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Basic Info')
                    ->icon(Heroicon::OutlinedBuildingStorefront)
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Shop Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('phone')
                            ->label('Phone')
                            ->tel(),

                        TextInput::make('email')
                            ->label('Email')
                            ->email(),

                        Textarea::make('description_en')
                            ->label('Description (English)')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('description_km')
                            ->label('Description (Khmer)')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Social Media')
                    ->icon(Heroicon::OutlinedShare)
                    ->columns(2)
                    ->schema([
                        TextInput::make('facebook_page')
                            ->label('Facebook Page URL')
                            ->url()
                            ->placeholder('https://facebook.com/yourpage')
                            ->prefixIcon(Heroicon::OutlinedGlobeAlt),

                        TextInput::make('telegram')
                            ->label('Telegram Username or Link')
                            ->placeholder('@yourshop or https://t.me/yourshop')
                            ->prefixIcon(Heroicon::OutlinedChatBubbleLeftRight),
                    ]),

                Section::make('Telegram Notifications')
                    ->description('Receive instant order notifications on Telegram. To find your Chat ID, message @userinfobot on Telegram — it will reply with your ID.')
                    ->icon(Heroicon::OutlinedBellAlert)
                    ->schema([
                        TextInput::make('telegram_chat_id')
                            ->label('Telegram Chat ID')
                            ->placeholder('e.g. 123456789')
                            ->helperText('Your numeric Telegram Chat ID. Message @userinfobot on Telegram to get it.'),
                    ]),

                Section::make('Apollo Delivery — Sender Location')
                    ->description('Set your shop\'s sender province and district so Apollo eDelivery can calculate shipping fees and create delivery bookings after each order is paid.')
                    ->icon(Heroicon::OutlinedTruck)
                    ->columns(2)
                    ->schema([
                        Select::make('apollo_province_id')
                            ->label('Sender Province')
                            ->options(function (): array {
                                try {
                                    $apollo     = app(ApolloDeliveryProvider::class);
                                    $provinces  = $apollo->getProvinces();

                                    return collect($provinces)
                                        ->pluck('name', 'id')
                                        ->toArray();
                                } catch (\Throwable) {
                                    return [];
                                }
                            })
                            ->searchable()
                            ->placeholder('Select province')
                            ->helperText('The province your shop ships orders from.')
                            ->reactive(),

                        Select::make('apollo_district_id')
                            ->label('Sender District')
                            ->options(function (callable $get): array {
                                $provinceId = $get('apollo_province_id');

                                if (! $provinceId) {
                                    return [];
                                }

                                try {
                                    $apollo    = app(ApolloDeliveryProvider::class);
                                    $districts = $apollo->getDistricts((int) $provinceId);

                                    return collect($districts)
                                        ->pluck('name', 'id')
                                        ->toArray();
                                } catch (\Throwable) {
                                    return [];
                                }
                            })
                            ->searchable()
                            ->placeholder('Select district (optional)')
                            ->helperText('Optional but improves fee accuracy.'),
                    ]),

                Section::make('Branding')
                    ->description('Upload a new file to replace the current logo or banner. Leave empty to keep existing.')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->columns(2)
                    ->schema([
                        FileUpload::make('logo')
                            ->label('Shop Logo')
                            ->image()
                            ->disk('public')
                            ->directory('shop-logos')
                            ->maxSize(2048)
                            ->helperText('Recommended: 400×400 px (square). Min 200×200 px. PNG or JPG, max 2 MB.'),

                        FileUpload::make('banner')
                            ->label('Shop Banner')
                            ->image()
                            ->disk('public')
                            ->directory('shop-banners')
                            ->maxSize(4096)
                            ->helperText('Recommended: 1200×400 px (3:1 ratio) or 1920×640 px. PNG or JPG, max 4 MB.'),
                    ]),
            ]);
    }

    public function save(): void
    {
        $shop = $this->getShop();

        if ($shop === null) {
            Notification::make()->title('No shop found')->danger()->send();
            return;
        }

        // getState() processes FileUpload: moves tmp files to the target disk/directory
        $data = $this->form->getState();

        $updateData = [
            'name'             => $data['name'],
            'phone'            => $data['phone'] ?: null,
            'email'            => $data['email'] ?: null,
            'facebook_page'    => $data['facebook_page'] ?: null,
            'telegram'         => $data['telegram'] ?: null,
            'telegram_chat_id'   => $data['telegram_chat_id'] ?: null,
            'apollo_province_id' => $data['apollo_province_id'] ? (int) $data['apollo_province_id'] : null,
            'apollo_district_id' => $data['apollo_district_id'] ? (int) $data['apollo_district_id'] : null,
            'description_i18n' => [
                'en' => $data['description_en'] ?? '',
                'km' => $data['description_km'] ?? '',
            ],
        ];

        $logo = $data['logo'] ?? null;
        $updateData['logo'] = !empty($logo)
            ? (is_array($logo) ? ($logo[0] ?? $shop->logo) : $logo)
            : $shop->logo;

        $banner = $data['banner'] ?? null;
        $updateData['banner'] = !empty($banner)
            ? (is_array($banner) ? ($banner[0] ?? $shop->banner) : $banner)
            : $shop->banner;

        $shop->update($updateData);

        Notification::make()->title('Settings saved')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->submit('save'),
        ];
    }

    private function getShop(): ?Shop
    {
        return Shop::where('owner_id', auth()->id())->first();
    }
}
