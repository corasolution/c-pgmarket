<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Contracts\PaymentGateway;
use App\Models\Order;

final class InitiatePayment
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    /**
     * @return array{transaction_id: string, form_data: array<string, string>}
     */
    public function __invoke(Order $order): array
    {
        return $this->gateway->createCheckout($order);
    }
}
