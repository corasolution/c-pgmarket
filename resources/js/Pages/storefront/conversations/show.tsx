import { FormEvent, useEffect, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/Storefront/StorefrontLayout';
import { usePage } from '@inertiajs/react';
import type { PageProps } from '@/types';

interface Sender {
    id: number;
    name: string;
    role: string;
}

interface Message {
    id: number;
    sender_id: number;
    sender: Sender;
    body: string;
    created_at: string;
}

interface Shop {
    id: number;
    name: string;
    logo: string | null;
}

interface Conversation {
    id: number;
    shop: Shop;
}

type SharedProps = PageProps;

interface Props {
    conversation: Conversation;
    messages: Message[];
    channelName: string;
}

export default function ConversationShow({ conversation, messages: initialMessages, channelName }: Props) {
    const { auth } = usePage<SharedProps>().props;
    const [messages, setMessages] = useState<Message[]>(initialMessages);
    const [body, setBody] = useState('');
    const [sending, setSending] = useState(false);
    const bottomRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    useEffect(() => {
        // Subscribe to private Reverb channel
        const channel = (window as unknown as { Echo: { private: (channel: string) => { listen: (event: string, cb: (data: Message) => void) => unknown } } }).Echo?.private(channelName);

        channel?.listen('.MessageSent', (data: Message) => {
            // Skip messages from the current user — they are already added
            // optimistically in the onSuccess callback.
            if (data.sender_id !== auth.user.id) {
                setMessages((prev) => [...prev, data]);
            }
        });

        return () => {
            (window as unknown as { Echo: { leave: (channel: string) => void } }).Echo?.leave(channelName);
        };
    }, [channelName]);

    function sendMessage(e: FormEvent) {
        e.preventDefault();
        if (!body.trim()) return;

        setSending(true);
        router.post(
            route('conversations.messages.store', conversation.id),
            { body },
            {
                preserveScroll: true,
                onSuccess: () => {
                    const optimistic: Message = {
                        id: Date.now(),
                        sender_id: auth.user.id,
                        sender: { id: auth.user.id, name: auth.user.name, role: auth.user.role },
                        body,
                        created_at: new Date().toISOString(),
                    };
                    setMessages((prev) => [...prev, optimistic]);
                    setBody('');
                    setSending(false);
                },
                onError: () => setSending(false),
            },
        );
    }

    return (
        <StorefrontLayout>
            <Head title={`Chat with ${conversation.shop.name}`} />
            <div className="max-w-2xl mx-auto px-4 py-6 flex flex-col h-[80vh]">
                {/* Header */}
                <div className="flex items-center gap-3 pb-4 border-b border-gray-100 mb-4">
                    <div className="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center overflow-hidden">
                        {conversation.shop.logo ? (
                            <img src={conversation.shop.logo} alt={conversation.shop.name} className="w-full h-full object-cover" />
                        ) : (
                            <span className="text-orange-600 font-bold">{conversation.shop.name[0]}</span>
                        )}
                    </div>
                    <h1 className="font-semibold text-gray-900">{conversation.shop.name}</h1>
                </div>

                {/* Messages */}
                <div className="flex-1 overflow-y-auto space-y-3 pr-1">
                    {messages.map((msg) => {
                        const isMine = msg.sender_id === auth.user.id;
                        return (
                            <div key={msg.id} className={`flex ${isMine ? 'justify-end' : 'justify-start'}`}>
                                <div
                                    className={`max-w-xs lg:max-w-md px-4 py-2 rounded-2xl text-sm ${
                                        isMine
                                            ? 'bg-orange-500 text-white rounded-br-sm'
                                            : 'bg-gray-100 text-gray-800 rounded-bl-sm'
                                    }`}
                                >
                                    <p>{msg.body}</p>
                                    <p className={`text-xs mt-1 ${isMine ? 'text-orange-200' : 'text-gray-400'}`}>
                                        {new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                    </p>
                                </div>
                            </div>
                        );
                    })}
                    <div ref={bottomRef} />
                </div>

                {/* Input */}
                <form onSubmit={sendMessage} className="flex gap-2 pt-4 border-t border-gray-100 mt-4">
                    <input
                        type="text"
                        value={body}
                        onChange={(e) => setBody(e.target.value)}
                        placeholder="Type a message…"
                        className="flex-1 border border-gray-200 rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400"
                        disabled={sending}
                    />
                    <button
                        type="submit"
                        disabled={sending || !body.trim()}
                        className="bg-orange-500 hover:bg-orange-600 text-white rounded-full px-5 py-2 text-sm font-medium transition disabled:opacity-40"
                    >
                        Send
                    </button>
                </form>
            </div>
        </StorefrontLayout>
    );
}


