<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ChatbotSetting extends Model
{
    protected $fillable = [
        'is_enabled',
        'provider',
        'claude_model',
        'claude_api_key',
        'gemini_api_key',
        'gemini_model',
        'system_prompt',
        'max_tokens',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled'     => 'boolean',
            'max_tokens'     => 'integer',
            'claude_api_key' => 'encrypted',
            'gemini_api_key' => 'encrypted',
        ];
    }

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'is_enabled'   => true,
            'provider'     => 'claude',
            'claude_model' => 'claude-3-5-haiku-20241022',
            'gemini_model' => 'gemini-2.0-flash',
            'max_tokens'   => 1024,
        ]);
    }
}
