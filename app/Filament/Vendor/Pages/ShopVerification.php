<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Pages;

use App\Models\Shop;
use App\Models\ShopVerification as ShopVerificationModel;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

final class ShopVerification extends Page
{
    protected string $view = 'filament.vendor.pages.shop-verification';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;
    protected static ?string $navigationLabel = 'Verification';
    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return 'Shop';
    }

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $shop = $this->getShop();
        $verification = $shop?->verification;

        $this->form->fill([
            'business_license'    => $verification?->business_license,
            'owner_id_front'      => $verification?->owner_id_front,
            'owner_id_back'       => $verification?->owner_id_back,
            'bank_name'           => $verification?->bank_name ?? '',
            'bank_account_name'   => $verification?->bank_account_name ?? '',
            'bank_account_number' => $verification?->bank_account_number ?? '',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $verification = $this->getShop()?->verification;
        $isApproved = $verification?->status === 'approved';

        return $schema
            ->statePath('data')
            ->components([
                Section::make('KYC Documents')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->description('Upload your business license and owner identification documents.')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('business_license')
                            ->label('Business License')
                            ->disk('public')
                            ->directory('kyc-documents')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                            ->maxSize(5120)
                            ->required()
                            ->helperText('Image or PDF, max 5 MB.')
                            ->columnSpanFull()
                            ->disabled($isApproved),

                        FileUpload::make('owner_id_front')
                            ->label('Owner ID (Front)')
                            ->disk('public')
                            ->directory('kyc-documents')
                            ->image()
                            ->maxSize(2048)
                            ->required()
                            ->helperText('Image, max 2 MB.')
                            ->disabled($isApproved),

                        FileUpload::make('owner_id_back')
                            ->label('Owner ID (Back)')
                            ->disk('public')
                            ->directory('kyc-documents')
                            ->image()
                            ->maxSize(2048)
                            ->required()
                            ->helperText('Image, max 2 MB.')
                            ->disabled($isApproved),
                    ]),

                Section::make('Bank Account')
                    ->icon(Heroicon::OutlinedBuildingLibrary)
                    ->description('Your bank account details for receiving payouts.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('bank_name')
                            ->label('Bank Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g. ABA Bank, ACLEDA Bank')
                            ->columnSpanFull()
                            ->disabled($isApproved),

                        TextInput::make('bank_account_name')
                            ->label('Account Holder Name')
                            ->required()
                            ->maxLength(255)
                            ->disabled($isApproved),

                        TextInput::make('bank_account_number')
                            ->label('Account Number')
                            ->required()
                            ->maxLength(255)
                            ->disabled($isApproved),
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

        $verification = $shop->verification;

        if ($verification?->status === 'approved') {
            Notification::make()->title('Verification already approved — cannot modify.')->warning()->send();
            return;
        }

        $data = $this->form->getState();

        ShopVerificationModel::updateOrCreate(
            ['shop_id' => $shop->id],
            [
                'business_license'    => $data['business_license'],
                'owner_id_front'      => $data['owner_id_front'],
                'owner_id_back'       => $data['owner_id_back'],
                'bank_name'           => $data['bank_name'],
                'bank_account_name'   => $data['bank_account_name'],
                'bank_account_number' => $data['bank_account_number'],
                'status'              => 'pending',
                'rejection_reason'    => null,
            ],
        );

        Notification::make()->title('Verification documents submitted successfully.')->success()->send();

        $this->redirect(static::getUrl());
    }

    protected function getHeaderActions(): array
    {
        $verification = $this->getShop()?->verification;

        if ($verification?->status === 'approved') {
            return [];
        }

        return [
            Action::make('save')
                ->label('Submit Verification')
                ->submit('save'),
        ];
    }

    private function getShop(): ?Shop
    {
        return Shop::where('owner_id', auth()->id())->first();
    }
}
