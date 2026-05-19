<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ChatbotKnowledgeBase extends Model
{
    protected $fillable = [
        'title',
        'content',
        'source_type',
        'source_url',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
