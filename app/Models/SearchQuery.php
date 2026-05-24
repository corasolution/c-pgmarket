<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class SearchQuery extends Model
{
    protected $fillable = ['query_text', 'user_id', 'category_slug', 'results_count'];

    protected function casts(): array
    {
        return ['results_count' => 'integer'];
    }
}
