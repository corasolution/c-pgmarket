<?php

declare(strict_types=1);

namespace App\Actions\Payout;

use App\Models\AbaBeneficiary;
use App\Models\AuditLog;
use App\Models\Shop;
use App\Services\Payment\AbaPayoutService;
use Illuminate\Support\Facades\Log;

final class DeactivateBeneficiary
{
    public function __construct(private readonly AbaPayoutService $payoutService) {}

    /**
     * Deactivate a whitelisted beneficiary (e.g. when a shop is suspended).
     */
    public function __invoke(Shop $shop, string $payee): void
    {
        $beneficiary = AbaBeneficiary::where('shop_id', $shop->id)
            ->where('payee', $payee)
            ->first();

        if ($beneficiary === null || $beneficiary->status === 'inactive') {
            return;
        }

        $response = $this->payoutService->updateBeneficiaryStatus($payee, 0);

        $statusCode = $response['status']['code'] ?? '';
        $isSuccess  = in_array($statusCode, ['200', '00'], strict: true);

        $before = ['status' => $beneficiary->status];

        if ($isSuccess) {
            $beneficiary->update(['status' => 'inactive', 'raw_response' => $response]);
        } else {
            Log::warning('ABA beneficiary deactivation failed', [
                'shop_id' => $shop->id,
                'payee' => $payee,
                'response' => $response,
            ]);
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'beneficiary.deactivate',
            'auditable_type' => AbaBeneficiary::class,
            'auditable_id' => $beneficiary->id,
            'before' => $before,
            'after' => ['status' => $isSuccess ? 'inactive' : $beneficiary->status, 'aba_code' => $statusCode],
        ]);
    }
}
