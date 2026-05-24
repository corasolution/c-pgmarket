<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Lightweight Telegram Bot API client.
 * Uses Laravel HTTP client — no extra package needed.
 */
final class TelegramService
{
    public function sendMessage(string $chatId, string $text, ?string $parseMode = 'HTML'): bool
    {
        $token = (string) env('TELEGRAM_BOT_TOKEN', '');

        if ($token === '' || $chatId === '') {
            return false;
        }

        try {
            $response = Http::timeout(10)->post(
                "https://api.telegram.org/bot{$token}/sendMessage",
                [
                    'chat_id'    => $chatId,
                    'text'       => $text,
                    'parse_mode' => $parseMode,
                ],
            );

            if (! $response->successful()) {
                Log::warning('Telegram sendMessage failed', [
                    'chat_id'  => $chatId,
                    'status'   => $response->status(),
                    'response' => $response->json(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Telegram sendMessage exception', [
                'chat_id' => $chatId,
                'error'   => $e->getMessage(),
            ]);

            return false;
        }
    }
}
