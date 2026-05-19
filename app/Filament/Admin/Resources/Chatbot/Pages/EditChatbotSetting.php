<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Chatbot\Pages;

use App\Filament\Admin\Resources\Chatbot\ChatbotSettingResource;
use Filament\Resources\Pages\EditRecord;

final class EditChatbotSetting extends EditRecord
{
    protected static string $resource = ChatbotSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Chatbot settings saved.';
    }
}
