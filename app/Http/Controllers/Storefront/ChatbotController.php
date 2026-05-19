<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class ChatbotController extends Controller
{
    public function __construct(private readonly ChatbotService $chatbotService) {}

    public function message(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message'          => ['required', 'string', 'max:1000'],
            'history'          => ['array', 'max:20'],
            'history.*.role'   => ['required', 'in:user,assistant'],
            'history.*.content' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $reply = $this->chatbotService->chat(
                userMessage: $validated['message'],
                history: $validated['history'] ?? [],
            );

            return response()->json(['reply' => $reply]);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        }
    }
}
