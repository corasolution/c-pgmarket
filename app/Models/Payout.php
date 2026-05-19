<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Payout extends Model
{
    use BelongsToShop, HasFactory;

    protected $fillable = [
        'shop_id', 'payout_batch_id', 'amount_cents', 'amount_currency', 'status',
        'bank_account_name', 'bank_account_number', 'bank_name', 'approved_by',
        'approved_at', 'rejection_reason', 'aba_transaction_id', 'aba_external_ref',
    ];

    protected function casts(): array
    {
        return ['amount_cents' => 'integer', 'approved_at' => 'datetime'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PayoutBatch::class, 'payout_batch_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
