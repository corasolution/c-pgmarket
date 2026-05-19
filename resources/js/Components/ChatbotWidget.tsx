import { useEffect, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';
import axios from 'axios';
import { Bot, ChevronDown, MessageCircle, RefreshCw, Send, X } from 'lucide-react';
import type { PageProps } from '@/types';

interface Message {
    role: 'user' | 'assistant';
    content: string;
}

function renderMessage(text: string, isUser: boolean) {
    // Split with a capturing group — odd-indexed parts are the captured URLs
    const parts = text.split(/(https?:\/\/[^\s)]+)/g);
    return parts.map((part, i) => {
        if (i % 2 === 1) {
            const label = part.replace(/^https?:\/\/[^/]+/, '') || '/';
            return (
                <a key={i} href={part} target="_blank" rel="noopener noreferrer"
                    className={`break-all underline underline-offset-2 ${isUser ? 'text-white/90 hover:text-white' : 'text-primary hover:text-primary/80'}`}>
                    {label}
                </a>
            );
        }
        return <span key={i}>{part}</span>;
    });
}

const WELCOME: Message = {
    role: 'assistant',
    content: "Hello! 👋 I'm the PG Market assistant. Ask me anything about our products, shops, or how to place an order!",
};

export default function ChatbotWidget() {
    const { chatbotEnabled } = usePage<PageProps>().props;

    const [isOpen, setIsOpen]         = useState(false);
    const [messages, setMessages]     = useState<Message[]>([WELCOME]);
    const [input, setInput]           = useState('');
    const [isLoading, setIsLoading]   = useState(false);
    const [error, setError]           = useState<string | null>(null);
    const bottomRef                   = useRef<HTMLDivElement>(null);
    const inputRef                    = useRef<HTMLInputElement>(null);

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages, isLoading]);

    useEffect(() => {
        if (isOpen) {
            setTimeout(() => inputRef.current?.focus(), 120);
        }
    }, [isOpen]);

    if (!chatbotEnabled) return null;

    async function sendMessage() {
        const text = input.trim();
        if (!text || isLoading) return;

        const userMsg: Message = { role: 'user', content: text };
        setInput('');
        setError(null);
        setMessages(prev => [...prev, userMsg]);
        setIsLoading(true);

        // history sent to API = all except the initial welcome greeting
        const apiHistory: Message[] = messages.slice(1);

        try {
            const { data } = await axios.post<{ reply?: string; error?: string }>(
                route('chatbot.message'),
                { message: text, history: apiHistory },
            );

            if (data.error) {
                setError(data.error);
            } else {
                setMessages(prev => [...prev, { role: 'assistant', content: data.reply ?? '' }]);
            }
        } catch {
            setError('Network error — please try again.');
        } finally {
            setIsLoading(false);
        }
    }

    function handleKey(e: React.KeyboardEvent<HTMLInputElement>) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            void sendMessage();
        }
    }

    function reset() {
        setMessages([WELCOME]);
        setError(null);
        setInput('');
    }

    return (
        <>
            {/* ── Chat window ── */}
            {isOpen && (
                <div
                    className="fixed bottom-24 right-5 z-9999 flex w-85 flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl"
                    style={{ height: 480 }}
                >
                    {/* Header */}
                    <div className="flex items-center justify-between bg-primary px-4 py-3 text-white">
                        <div className="flex items-center gap-2.5">
                            <div className="flex h-9 w-9 items-center justify-center rounded-full bg-white/20">
                                <Bot className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="text-sm font-bold leading-tight">PG Market Assistant</p>
                                <p className="text-[10px] text-white/65">Powered by AI</p>
                            </div>
                        </div>
                        <div className="flex items-center gap-1">
                            <button
                                onClick={reset}
                                title="New conversation"
                                className="rounded-full p-1.5 text-white/70 hover:bg-white/20 hover:text-white transition-colors"
                            >
                                <RefreshCw className="h-3.5 w-3.5" />
                            </button>
                            <button
                                onClick={() => setIsOpen(false)}
                                title="Minimise"
                                className="rounded-full p-1.5 text-white/70 hover:bg-white/20 hover:text-white transition-colors"
                            >
                                <ChevronDown className="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    {/* Messages */}
                    <div className="flex flex-1 flex-col gap-3 overflow-y-auto px-4 py-4">
                        {messages.map((msg, i) => (
                            <div
                                key={i}
                                className={`flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}
                            >
                                {msg.role === 'assistant' && (
                                    <div className="mr-2 mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/10">
                                        <Bot className="h-3.5 w-3.5 text-primary" />
                                    </div>
                                )}
                                <div
                                    className={`max-w-[80%] rounded-2xl px-3.5 py-2.5 text-sm leading-relaxed ${
                                        msg.role === 'user'
                                            ? 'rounded-tr-sm bg-primary text-white'
                                            : 'rounded-tl-sm bg-gray-100 text-gray-800'
                                    }`}
                                >
                                    {renderMessage(msg.content, msg.role === 'user')}
                                </div>
                            </div>
                        ))}

                        {/* Typing indicator */}
                        {isLoading && (
                            <div className="flex items-start gap-2">
                                <div className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/10">
                                    <Bot className="h-3.5 w-3.5 text-primary" />
                                </div>
                                <div className="flex items-center gap-1 rounded-2xl rounded-tl-sm bg-gray-100 px-4 py-3">
                                    <span className="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400 [animation-delay:0ms]" />
                                    <span className="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400 [animation-delay:150ms]" />
                                    <span className="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400 [animation-delay:300ms]" />
                                </div>
                            </div>
                        )}

                        {/* Error notice */}
                        {error && (
                            <p className="rounded-xl bg-red-50 px-3 py-2 text-center text-xs text-red-600">
                                {error}
                            </p>
                        )}

                        <div ref={bottomRef} />
                    </div>

                    {/* Input */}
                    <div className="border-t border-gray-100 px-3 py-3">
                        <div className="flex items-center gap-2">
                            <input
                                ref={inputRef}
                                value={input}
                                onChange={e => setInput(e.target.value)}
                                onKeyDown={handleKey}
                                placeholder="Type your question…"
                                disabled={isLoading}
                                className="flex-1 rounded-full border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:bg-white disabled:opacity-50"
                            />
                            <button
                                onClick={() => void sendMessage()}
                                disabled={!input.trim() || isLoading}
                                className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary text-white shadow-sm transition hover:bg-primary/90 disabled:opacity-40"
                            >
                                <Send className="h-3.5 w-3.5" />
                            </button>
                        </div>
                        <p className="mt-1.5 text-center text-[10px] text-gray-300">AI can make mistakes — verify important info</p>
                    </div>
                </div>
            )}

            {/* ── Floating toggle button ── */}
            <button
                onClick={() => setIsOpen(v => !v)}
                aria-label={isOpen ? 'Close chat' : 'Open chat'}
                className="fixed bottom-6 right-5 z-9999 flex h-14 w-14 items-center justify-center rounded-full bg-primary text-white shadow-xl transition-all duration-200 hover:scale-110 hover:shadow-2xl active:scale-95"
            >
                {isOpen
                    ? <X className="h-6 w-6" />
                    : <MessageCircle className="h-6 w-6" />}

                {/* Pulse ring when closed */}
                {!isOpen && (
                    <span className="absolute h-full w-full animate-ping rounded-full bg-primary opacity-20" />
                )}
            </button>
        </>
    );
}
