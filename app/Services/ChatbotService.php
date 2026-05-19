<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ChatbotKnowledgeBase;
use App\Models\ChatbotSetting;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class ChatbotService
{
    private const CLAUDE_API_URL = 'https://api.anthropic.com/v1/messages';
    private const CLAUDE_API_VERSION = '2023-06-01';
    private const GEMINI_API_BASE = 'https://generativelanguage.googleapis.com/v1beta/models';

    /** Common English/Khmer stop-words to ignore when extracting search keywords */
    private const STOP_WORDS = [
        'the', 'and', 'for', 'are', 'you', 'can', 'has', 'how', 'what',
        'when', 'where', 'who', 'which', 'this', 'that', 'with', 'have',
        'from', 'they', 'will', 'been', 'not', 'but', 'any', 'all',
        'its', 'our', 'your', 'their', 'want', 'need', 'get', 'got',
        'looking', 'find', 'show', 'tell', 'about', 'some', 'like',
    ];

    public function chat(string $userMessage, array $history = []): string
    {
        $setting = ChatbotSetting::current();

        if (! $setting->is_enabled) {
            throw new RuntimeException('Chatbot is currently disabled.');
        }

        $provider = $setting->provider ?? 'claude';

        // Validate API key for the selected provider
        if ($provider === 'claude' && empty($setting->claude_api_key)) {
            throw new RuntimeException('Claude API key is not configured.');
        }
        if ($provider === 'gemini' && empty($setting->gemini_api_key)) {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        $kbEntries    = $this->getKbEntries();
        $productBlock = $this->buildProductContext($userMessage);
        $systemPrompt = $this->buildSystemPrompt($setting->system_prompt, $kbEntries, $productBlock);
        $messages     = $this->buildMessages($history, $userMessage);

        return match ($provider) {
            'gemini' => $this->callGemini($setting, $systemPrompt, $messages),
            default  => $this->callClaude($setting, $systemPrompt, $messages),
        };
    }

    // ──────────────────────────────────────────────────────────────────────────
    // AI Provider calls
    // ──────────────────────────────────────────────────────────────────────────

    private function callClaude(ChatbotSetting $setting, string $systemPrompt, array $messages): string
    {
        $response = Http::withHeaders([
            'x-api-key'         => $setting->claude_api_key,
            'anthropic-version' => self::CLAUDE_API_VERSION,
            'content-type'      => 'application/json',
        ])->timeout(30)->post(self::CLAUDE_API_URL, [
            'model'      => $setting->claude_model,
            'max_tokens' => $setting->max_tokens,
            'system'     => $systemPrompt,
            'messages'   => $messages,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Failed to reach Claude API: '.$response->status());
        }

        return (string) ($response->json('content.0.text') ?? 'Sorry, I could not generate a response right now.');
    }

    private function callGemini(ChatbotSetting $setting, string $systemPrompt, array $messages): string
    {
        $model = $setting->gemini_model ?? 'gemini-2.0-flash';
        $url = self::GEMINI_API_BASE."/{$model}:generateContent?key={$setting->gemini_api_key}";

        // Convert messages from Claude format (user/assistant) to Gemini format (user/model)
        $contents = [];
        foreach ($messages as $msg) {
            $role = $msg['role'] === 'assistant' ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $msg['content']]],
            ];
        }

        $response = Http::timeout(30)->post($url, [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'maxOutputTokens' => $setting->max_tokens,
            ],
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Failed to reach Gemini API: '.$response->status());
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        return (string) ($text ?? 'Sorry, I could not generate a response right now.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Product search
    // ──────────────────────────────────────────────────────────────────────────

    private function buildProductContext(string $userMessage): string
    {
        $keywords = $this->extractKeywords($userMessage);

        if ($keywords->isEmpty()) {
            return '';
        }

        /** @var Collection<int, Product> $products */
        $products = Product::query()
            ->where('status', 'active')
            ->where(function ($q) use ($keywords): void {
                foreach ($keywords as $kw) {
                    $pattern = '%'.addcslashes($kw, '%_').'%';
                    $q->orWhereRaw("LOWER(name_i18n->>'en') LIKE LOWER(?)", [$pattern])
                      ->orWhereRaw("LOWER(name_i18n->>'km') LIKE LOWER(?)", [$pattern])
                      ->orWhereRaw("LOWER(description_i18n->>'en') LIKE LOWER(?)", [$pattern]);
                }
            })
            ->with([
                'shop:id,name,slug',
                'variants' => fn ($q) => $q
                    ->where('is_active', true)
                    ->orderBy('price_cents')
                    ->limit(1)
                    ->select(['id', 'product_id', 'price_cents', 'price_currency']),
            ])
            ->limit(8)
            ->get(['id', 'name_i18n', 'description_i18n', 'slug', 'shop_id']);

        if ($products->isEmpty()) {
            return '';
        }

        $lines = $products->map(function (Product $product): string {
            $name  = $product->name_i18n['en'] ?? ($product->name_i18n['km'] ?? 'Product');
            $desc  = $product->description_i18n['en'] ?? '';
            $shop  = $product->shop?->name ?? 'PG Market';
            $url   = url('/products/'.$product->slug);
            $shopUrl = $product->shop ? url('/shops/'.$product->shop->slug) : null;

            /** @var ProductVariant|null $variant */
            $variant = $product->variants->first();
            $price   = $variant ? $this->formatPrice($variant->price_cents, $variant->price_currency) : null;

            $snippet = $desc !== '' ? mb_substr($desc, 0, 120).(mb_strlen($desc) > 120 ? '…' : '') : null;

            $line = "• {$name}";
            if ($price !== null) {
                $line .= " — {$price}";
            }
            $line .= "\n  Shop: {$shop}";
            if ($shopUrl !== null) {
                $line .= " ({$shopUrl})";
            }
            $line .= "\n  Product link: {$url}";
            if ($snippet !== null) {
                $line .= "\n  {$snippet}";
            }

            return $line;
        })->implode("\n\n");

        return $lines;
    }

    /**
     * @return Collection<int, string>
     */
    private function extractKeywords(string $message): Collection
    {
        /** @var Collection<int, string> $words */
        $words = collect(preg_split('/\s+/', mb_strtolower($message)) ?: [])
            ->map(fn (string $w): string => (string) preg_replace('/[^a-z0-9]/u', '', $w))
            ->filter(fn (string $w): bool => mb_strlen($w) >= 3 && ! in_array($w, self::STOP_WORDS, true))
            ->unique()
            ->take(5)
            ->values();

        return $words;
    }

    private function formatPrice(int $cents, string $currency): string
    {
        $amount = $cents / 100;

        return match (strtoupper($currency)) {
            'KHR'   => number_format($amount, 0).' ៛',
            default => '$'.number_format($amount, 2),
        };
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Prompt assembly
    // ──────────────────────────────────────────────────────────────────────────

    /** @return Collection<int, ChatbotKnowledgeBase> */
    private function getKbEntries(): Collection
    {
        return ChatbotKnowledgeBase::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['title', 'content']);
    }

    /**
     * @param Collection<int, ChatbotKnowledgeBase> $kbEntries
     */
    private function buildSystemPrompt(
        ?string $customPrompt,
        Collection $kbEntries,
        string $productBlock,
    ): string {
        $base = filled($customPrompt)
            ? $customPrompt
            : 'You are a friendly and helpful customer service assistant for PG Market, a multi-vendor marketplace based in Cambodia. When recommending products always include their direct link. Answer concisely. If you do not know something, say so honestly.';

        if ($kbEntries->isNotEmpty()) {
            $kb    = $kbEntries->map(fn (ChatbotKnowledgeBase $e) => "### {$e->title}\n{$e->content}")->implode("\n\n");
            $base .= "\n\n## Knowledge Base\n\n".$kb;
        }

        if (filled($productBlock)) {
            $base .= "\n\n## Matching Products from Catalogue\n\nThe following products were found in our database that may relate to the customer's question. Share their links when recommending:\n\n".$productBlock;
        }

        return $base;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Message history
    // ──────────────────────────────────────────────────────────────────────────

    /** @param array<int, array{role: string, content: string}> $history */
    private function buildMessages(array $history, string $userMessage): array
    {
        $messages = [];

        foreach (array_slice($history, -10) as $h) {
            if (in_array($h['role'] ?? '', ['user', 'assistant'], true)) {
                $messages[] = ['role' => $h['role'], 'content' => (string) $h['content']];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        return $messages;
    }
}
