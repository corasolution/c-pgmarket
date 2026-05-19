<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Chatbot\Pages;

use App\Filament\Admin\Resources\Chatbot\ChatbotKnowledgeBaseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListChatbotKnowledgeBases extends ListRecords
{
    protected static string $resource = ChatbotKnowledgeBaseResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
