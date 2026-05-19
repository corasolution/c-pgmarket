<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PayoutBatch extends Model
{
    protected $fillable = [
        'reference', 'status', 'total_cents', 'total_currency',
        'payout_count', 'created_by', 'processed_at',
    ];

    protected function casts(): array
    {
        return ['total_cents' => 'integer', 'processed_at' => 'datetime'];
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
