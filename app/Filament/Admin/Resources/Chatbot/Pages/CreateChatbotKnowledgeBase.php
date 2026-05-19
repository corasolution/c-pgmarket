<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Chatbot\Pages;

use App\Filament\Admin\Resources\Chatbot\ChatbotKnowledgeBaseResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateChatbotKnowledgeBase extends CreateRecord
{
    protected static string $resource = ChatbotKnowledgeBaseResource::class;
}
