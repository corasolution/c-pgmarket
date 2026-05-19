import { FormEvent, useEffect, useRef, useState } from 'react';
import { MessageSquare, X, Send, Minus } from 'lucide-react';
import axios from 'axios';

interface Message {
    id: number;
    sender_id: number;
    body: string;
    created_at: string;
}

interface Props {
    shopId: number;
    shopName: string;
    shopLogo: string | null;
    currentUserId: number;
}

export default function FloatingChat({ shopId, shopName, shopLogo, currentUserId }: Props) {
    const [open, setOpen] = useState(false);
    const [minimised, setMinimised] = useState(false);
    const [messages, setMessages] = useState<Message[]>([]);
    const [conversationId, setConversationId] = useState<number | null>(null);
    const [body, setBody] = useState('');
    const [sending, setSending] = useState(false);
    const [loading, setLoading] = useState(false);
    const bottomRef = useRef<HTMLDivElement>(null);

    // Load conversation when opened
    useEffect(() => {
        if (!open || conversationId) return;
        setLoading(true);
        axios
            .get(route('conversations.api.shop', shopId))
            .then((res) => {
                setConversationId(res.data.conversation_id);
                setMessages(res.data.messages);
            })
            .finally(() => setLoading(false));
    }, [open, conversationId, shopId]);

    // Scroll to bottom on new messages
    useEffect(() => {
        if (open && !minimised) {
            bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
        }
    }, [messages, open, minimised]);

    function sendMessage(e: FormEvent) {
        e.preventDefault();
        if (!body.trim() || !conversationId || sending) return;

        const optimistic: Message = {
            id: Date.now(),
            sender_id: currentUserId,
            body: body.trim(),
            created_at: new Date().toISOString(),
        };

        setMessages((prev) => [...prev, optimistic]);
        const sent = body.trim();
        setBody('');
        setSending(true);

        axios
            .post(route('conversations.messages.store', conversationId), { body: sent })
            .then((res) => {
                // Replace optimistic with real message from server
                setMessages((prev) => prev.map((m) => (m.id === optimistic.id ? res.data : m)));
            })
            .catch(() => {
                // Remove optimistic on failure
                setMessages((prev) => prev.filter((m) => m.id !== optimistic.id));
                setBody(sent);
            })
            .finally(() => setSending(false));
    }

    return (
        <div className="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-3">
            {/* Chat window */}
            {open && (
                <div className="w-80 bg-white rounded-2xl shadow-2xl border border-gray-100 flex flex-col overflow-hidden">
                    {/* Header */}
                    <div className="flex items-center gap-3 px-4 py-3 bg-primary text-white">
                        <div className="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center overflow-hidden shrink-0">
                            {shopLogo ? (
                                <img src={shopLogo} alt={shopName} className="w-full h-full object-cover" />
                            ) : (
                                <span className="text-sm font-bold">{shopName[0]}</span>
                            )}
                        </div>
                        <span className="font-semibold text-sm flex-1 truncate">{shopName}</span>
                        <button
                            onClick={() => setMinimised((m) => !m)}
                            className="p-1 hover:bg-white/20 rounded-lg transition"
                            title={minimised ? 'Expand' : 'Minimise'}
                        >
                            <Minus className="w-4 h-4" />
                        </button>
                        <button
                            onClick={() => setOpen(false)}
                            className="p-1 hover:bg-white/20 rounded-lg transition"
                            title="Close"
                        >
                            <X className="w-4 h-4" />
                        </button>
                    </div>

                    {!minimised && (
                        <>
                            {/* Messages */}
                            <div className="flex-1 overflow-y-auto p-3 space-y-2 bg-gray-50" style={{ height: '320px' }}>
                                {loading && (
                                    <div className="flex items-center justify-center h-full text-gray-400 text-sm">
                                        Loading…
                                    </div>
                                )}
                                {!loading && messages.length === 0 && (
                                    <div className="flex items-center justify-center h-full text-center text-gray-400 text-sm px-4">
                                        <div>
                                            <MessageSquare className="w-8 h-8 mx-auto mb-2 opacity-30" />
                                            <p>Send a message to start the conversation</p>
                                        </div>
                                    </div>
                                )}
                                {messages.map((msg) => {
                                    const isMine = msg.sender_id === currentUserId;
                                    return (
                                        <div key={msg.id} className={`flex ${isMine ? 'justify-end' : 'justify-start'}`}>
                                            <div
                                                className={`max-w-[75%] px-3 py-2 rounded-2xl text-sm ${
                                                    isMine
                                                        ? 'bg-primary text-white rounded-br-sm'
                                                        : 'bg-white text-gray-800 shadow-sm rounded-bl-sm'
                                                }`}
                                            >
                                                <p className="break-words">{msg.body}</p>
                                                <p className={`text-[10px] mt-0.5 ${isMine ? 'text-orange-200' : 'text-gray-400'}`}>
                                                    {new Date(msg.created_at).toLocaleTimeString([], {
                                                        hour: '2-digit',
                                                        minute: '2-digit',
                                                    })}
                                                </p>
                                            </div>
                                        </div>
                                    );
                                })}
                                <div ref={bottomRef} />
                            </div>

                            {/* Input */}
                            <form onSubmit={sendMessage} className="flex items-center gap-2 p-3 border-t bg-white">
                                <input
                                    type="text"
                                    value={body}
                                    onChange={(e) => setBody(e.target.value)}
                                    placeholder="Type a message…"
                                    className="flex-1 bg-gray-100 rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                                    disabled={sending || loading}
                                    autoFocus
                                />
                                <button
                                    type="submit"
                                    disabled={!body.trim() || sending || loading}
                                    className="w-9 h-9 bg-primary hover:bg-primary-dark text-white rounded-full flex items-center justify-center transition disabled:opacity-40 shrink-0"
                                >
                                    <Send className="w-4 h-4" />
                                </button>
                            </form>
                        </>
                    )}
                </div>
            )}

            {/* FAB toggle button */}
            <button
                onClick={() => {
                    setOpen((o) => !o);
                    setMinimised(false);
                }}
                className="w-14 h-14 bg-primary hover:bg-primary-dark text-white rounded-full shadow-lg flex items-center justify-center transition-all hover:scale-105 active:scale-95"
                title="Chat with shop"
            >
                {open ? <X className="w-6 h-6" /> : <MessageSquare className="w-6 h-6" />}
            </button>
        </div>
    );
}


