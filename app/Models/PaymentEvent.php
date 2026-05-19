<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PaymentEvent extends Model
{
    protected $fillable = [
        'payment_id', 'external_event_id', 'provider', 'event_type', 'raw_payload', 'ip_address',
    ];

    protected function casts(): array
    {
        return ['raw_payload' => 'array'];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
