<x-filament-panels::page>
    @php
        $conversation = $this->getRecord();
        $messages = $conversation->messages()->with('sender')->orderBy('created_at', 'asc')->get();
        $currentUserId = auth()->id();
    @endphp

    {{-- Conversation Header --}}
    <div style="border-radius: 12px; border: 1px solid #e5e7eb; background: #fff; padding: 16px 20px; margin-bottom: 16px; display: flex; align-items: center; gap: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.06);">
        <div style="width: 40px; height: 40px; border-radius: 50%; background: #dbeafe; display: flex; align-items: center; justify-content: center;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#3b82f6" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
        </div>
        <div>
            <p style="font-weight: 600; color: #111827; margin: 0; font-size: 15px;">{{ $conversation->buyer?->name ?? 'Unknown Buyer' }}</p>
            <p style="font-size: 12px; color: #6b7280; margin: 2px 0 0;">{{ $conversation->buyer?->email ?? '' }}</p>
        </div>
    </div>

    {{-- Messages Thread --}}
    <div style="border-radius: 12px; border: 1px solid #e5e7eb; background: #f9fafb; padding: 20px; min-height: 400px; max-height: 600px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px;" id="messages-container">
        @forelse($messages as $message)
            @php
                $isMine = $message->sender_id === $currentUserId;
            @endphp
            <div style="display: flex; justify-content: {{ $isMine ? 'flex-end' : 'flex-start' }};">
                <div style="max-width: 70%; padding: 10px 14px; border-radius: 12px;
                    {{ $isMine
                        ? 'background: #3b82f6; color: #fff; border-bottom-right-radius: 4px;'
                        : 'background: #fff; color: #111827; border: 1px solid #e5e7eb; border-bottom-left-radius: 4px;'
                    }}
                ">
                    @if(!$isMine)
                    <p style="font-size: 11px; font-weight: 600; margin: 0 0 4px; color: #6b7280;">{{ $message->sender?->name ?? 'Unknown' }}</p>
                    @endif
                    <p style="margin: 0; font-size: 14px; line-height: 1.5; word-break: break-word;">{{ $message->body }}</p>
                    <p style="margin: 4px 0 0; font-size: 11px; text-align: right;
                        {{ $isMine ? 'color: rgba(255,255,255,.7);' : 'color: #9ca3af;' }}
                    ">{{ $message->created_at->format('M d, g:i A') }}</p>
                </div>
            </div>
        @empty
            <div style="text-align: center; padding: 60px 20px; color: #9ca3af;">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 12px;"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
                <p style="font-size: 15px; font-weight: 500;">No messages yet</p>
                <p style="font-size: 13px;">Start the conversation by sending a message below.</p>
            </div>
        @endforelse
    </div>

    {{-- Reply Input --}}
    <div style="margin-top: 16px;">
        <form wire:submit="sendMessage" style="display: flex; gap: 12px; align-items: flex-end;">
            <div style="flex: 1;">
                <textarea
                    wire:model="messageBody"
                    placeholder="Type your reply..."
                    rows="2"
                    style="width: 100%; padding: 10px 14px; border-radius: 10px; border: 1px solid #d1d5db; font-size: 14px; resize: vertical; min-height: 44px; font-family: inherit; outline: none;"
                    onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 2px rgba(59,130,246,.15)';"
                    onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none';"
                ></textarea>
            </div>
            <x-filament::button type="submit" icon="heroicon-o-paper-airplane">
                Send
            </x-filament::button>
        </form>
    </div>

    <script>
        // Auto-scroll to bottom on load
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('messages-container');
            if (container) container.scrollTop = container.scrollHeight;
        });

        // Auto-scroll after Livewire updates
        document.addEventListener('livewire:navigated', function () {
            const container = document.getElementById('messages-container');
            if (container) container.scrollTop = container.scrollHeight;
        });
    </script>

    <x-filament-actions::modals />
</x-filament-panels::page>
