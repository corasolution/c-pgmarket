<?php

declare(strict_types=1);

namespace App\Actions\Payout;

use App\Models\AbaBeneficiary;
use App\Models\AuditLog;
use App\Models\Shop;
use App\Services\Payment\AbaPayoutService;
use Illuminate\Support\Facades\Log;

final class WhitelistBeneficiary
{
    public function __construct(private readonly AbaPayoutService $payoutService) {}

    /**
     * Whitelist a vendor's ABA account for payout.
     *
     * @param  Shop    $shop   The vendor's shop
     * @param  string  $payee  ABA account number or Merchant ID
     */
    public function __invoke(Shop $shop, string $payee): AbaBeneficiary
    {
        $response = $this->payoutService->addBeneficiary($payee);

        $statusCode = $response['status']['code'] ?? '';
        $isSuccess  = in_array($statusCode, ['200', '00'], strict: true);
        $isAlreadyExists = $statusCode === 'PTL148';

        $beneficiaryStatus = match (true) {
            $isSuccess, $isAlreadyExists => 'active',
            default => 'failed',
        };

        $beneficiary = AbaBeneficiary::updateOrCreate(
            ['shop_id' => $shop->id, 'payee' => $payee],
            [
                'payee_name' => $response['data']['name'] ?? null,
                'status' => $beneficiaryStatus,
                'raw_response' => $response,
            ],
        );

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'beneficiary.whitelist',
            'auditable_type' => AbaBeneficiary::class,
            'auditable_id' => $beneficiary->id,
            'before' => null,
            'after' => [
                'payee' => $payee,
                'status' => $beneficiaryStatus,
                'aba_code' => $statusCode,
            ],
        ]);

        if (! $isSuccess && ! $isAlreadyExists) {
            $message = $response['status']['message'] ?? 'Unknown error';
            Log::error('ABA beneficiary whitelisting failed', [
                'shop_id' => $shop->id,
                'payee' => $payee,
                'code' => $statusCode,
                'message' => $message,
            ]);

            throw new \RuntimeException("Failed to whitelist beneficiary: {$message} (code: {$statusCode})");
        }

        return $beneficiary;
    }
}
