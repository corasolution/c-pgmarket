<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Chatbot\Pages;

use App\Filament\Admin\Resources\Chatbot\ChatbotKnowledgeBaseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditChatbotKnowledgeBase extends EditRecord
{
    protected static string $resource = ChatbotKnowledgeBaseResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
