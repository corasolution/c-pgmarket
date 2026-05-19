<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Model;

final class AbaBeneficiary extends Model
{
    use BelongsToShop;

    protected $fillable = [
        'shop_id', 'payee', 'payee_name', 'status', 'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'raw_response' => 'array',
        ];
    }
}
