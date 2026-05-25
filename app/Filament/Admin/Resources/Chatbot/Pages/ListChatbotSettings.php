<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Chatbot\Pages;

use App\Filament\Admin\Resources\Chatbot\ChatbotSettingResource;
use App\Models\ChatbotSetting;
use Filament\Resources\Pages\ListRecords;

final class ListChatbotSettings extends ListRecords
{
    protected static string $resource = ChatbotSettingResource::class;

    public function mount(): void
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

        $this->redirect(ChatbotSettingResource::getUrl('edit', ['record' => $setting->getKey()]));
    }
}
