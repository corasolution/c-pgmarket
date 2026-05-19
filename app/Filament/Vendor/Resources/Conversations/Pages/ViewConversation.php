<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\Conversations\Pages;

use App\Filament\Vendor\Resources\Conversations\ConversationResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Shop;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Livewire\Attributes\On;

final class ViewConversation extends ViewRecord
{
    protected static string $resource = ConversationResource::class;

    protected string $view = 'filament.vendor.pages.view-conversation';

    public string $messageBody = '';

    public function getTitle(): string
    {
        /** @var Conversation $conversation */
        $conversation = $this->getRecord();

        return 'Conversation with ' . ($conversation->buyer?->name ?? 'Unknown Buyer');
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Mark unread messages as read
        /** @var Conversation $conversation */
        $conversation = $this->getRecord();
        $conversation->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', auth()->id())
            ->update(['read_at' => now()]);
    }

    public function sendMessage(): void
    {
        $body = trim($this->messageBody);

        if ($body === '') {
            return;
        }

        /** @var Conversation $conversation */
        $conversation = $this->getRecord();

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => auth()->id(),
            'body'            => $body,
        ]);

        $conversation->update(['last_message_at' => now()]);

        $this->messageBody = '';

        Notification::make()->title('Message sent')->success()->send();
    }

    #[On('refresh-messages')]
    public function refreshMessages(): void
    {
        // Livewire will re-render the view automatically
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to Messages')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(ConversationResource::getUrl('index')),
        ];
    }
}
