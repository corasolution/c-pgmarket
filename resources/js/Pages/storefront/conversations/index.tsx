import { Head, Link } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/Storefront/StorefrontLayout';

interface Shop {
    id: number;
    name: string;
    logo: string | null;
}

interface Message {
    id: number;
    body: string;
    created_at: string;
}

interface Conversation {
    id: number;
    shop: Shop;
    last_message_at: string;
    messages: Message[];
}

interface Props {
    conversations: Conversation[];
}

export default function ConversationsIndex({ conversations }: Props) {
    return (
        <StorefrontLayout>
            <Head title="Messages" />
            <div className="max-w-2xl mx-auto px-4 py-8">
                <h1 className="text-2xl font-bold text-gray-900 mb-6">Messages</h1>

                {conversations.length === 0 ? (
                    <div className="text-center py-16 text-gray-400">
                        <svg className="w-12 h-12 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <p>No conversations yet</p>
                    </div>
                ) : (
                    <div className="space-y-2">
                        {conversations.map((conv) => (
                            <Link
                                key={conv.id}
                                href={route('conversations.show', conv.id)}
                                className="flex items-center gap-4 p-4 bg-white rounded-xl border border-gray-100 hover:border-orange-200 hover:shadow-sm transition"
                            >
                                <div className="w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center shrink-0 overflow-hidden">
                                    {conv.shop.logo ? (
                                        <img src={conv.shop.logo} alt={conv.shop.name} className="w-full h-full object-cover" />
                                    ) : (
                                        <span className="text-orange-600 font-bold text-lg">{conv.shop.name[0]}</span>
                                    )}
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="font-semibold text-gray-900">{conv.shop.name}</p>
                                    {conv.messages.at(-1) && (
                                        <p className="text-sm text-gray-500 truncate">{conv.messages.at(-1)!.body}</p>
                                    )}
                                </div>
                                <span className="text-xs text-gray-400 shrink-0">
                                    {new Date(conv.last_message_at).toLocaleDateString()}
                                </span>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </StorefrontLayout>
    );
}


