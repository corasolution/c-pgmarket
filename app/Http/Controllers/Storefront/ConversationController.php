<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Events\Chat\MessageSent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreConversationRequest;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ConversationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $conversations = Conversation::query()
            ->where('buyer_id', $user->id)
            ->with(['shop:id,name,logo', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->latest('last_message_at')
            ->get();

        return Inertia::render('storefront/conversations/index', [
            'conversations' => $conversations,
        ]);
    }

    public function show(Request $request, Conversation $conversation): Response
    {
        $user = $request->user();

        // Only buyer or shop's vendor may view
        $isShopVendor = $user->shop_id === $conversation->shop_id && $user->isVendor();
        if ($conversation->buyer_id !== $user->id && ! $isShopVendor && ! $user->isAdmin()) {
            abort(403);
        }

        $messages = $conversation->messages()
            ->with('sender:id,name,role')
            ->oldest()
            ->get();

        return Inertia::render('storefront/conversations/show', [
            'conversation' => $conversation->load('shop:id,name,logo'),
            'messages' => $messages,
            'channelName' => "conversation.{$conversation->id}",
        ]);
    }

    /**
     * JSON: find or create a conversation with a shop, return it with messages.
     * Called by the FloatingChat widget via axios.
     */
    public function apiWithShop(Request $request, int $shopId): JsonResponse
    {
        $conversation = Conversation::firstOrCreate(
            ['buyer_id' => $request->user()->id, 'shop_id' => $shopId],
            ['last_message_at' => now()],
        );

        $messages = $conversation->messages()
            ->with('sender:id,name')
            ->oldest()
            ->get(['id', 'sender_id', 'body', 'created_at']);

        return response()->json([
            'conversation_id' => $conversation->id,
            'messages' => $messages,
        ]);
    }

    /**
     * Find or create a conversation with a shop, then redirect to it.
     * Called from the "Chat with Shop" button on the shop profile page.
     */
    public function startWithShop(Request $request, int $shopId): \Illuminate\Http\RedirectResponse
    {
        $conversation = Conversation::firstOrCreate(
            ['buyer_id' => $request->user()->id, 'shop_id' => $shopId],
            ['last_message_at' => now()],
        );

        return redirect()->route('conversations.show', $conversation->id);
    }

    public function store(StoreConversationRequest $request): JsonResponse
    {
        $conversation = Conversation::firstOrCreate(
            ['buyer_id' => $request->user()->id, 'shop_id' => $request->validated('shop_id')],
            ['last_message_at' => now()],
        );

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body'      => $request->validated('body'),
        ]);

        $conversation->update(['last_message_at' => now()]);

        $message->load('sender:id,name,role');
        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message, 201);
    }

    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        $isShopVendor = $user->shop_id === $conversation->shop_id && $user->isVendor();

        if ($conversation->buyer_id !== $user->id && ! $isShopVendor && ! $user->isAdmin()) {
            abort(403);
        }

        $request->validate(['body' => 'required|string|max:2000']);

        $message = $conversation->messages()->create([
            'sender_id' => $user->id,
            'body' => $request->input('body'),
        ]);

        $conversation->update(['last_message_at' => now()]);

        $message->load('sender:id,name,role');
        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message, 201);
    }
}
