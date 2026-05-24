<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Order\CreateOrder;
use App\Actions\Payment\InitiatePayment;
use App\Contracts\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CheckoutApiController extends Controller
{
    public function store(
        Request $request,
        CreateOrder $createOrder,
        InitiatePayment $initiatePayment,
    ): JsonResponse {
        $validated = $request->validate([
            'shipping_address' => ['required', 'array'],
            'shipping_address.name' => ['required', 'string', 'max:255'],
            'shipping_address.phone' => ['required', 'string', 'max:30'],
            'shipping_address.address_line' => ['required', 'string', 'max:500'],
            'shipping_address.city' => ['required', 'string', 'max:100'],
            'shipping_address.province' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['nullable', 'string', 'in:aba_khqr,cod'],
            'promo_code' => ['nullable', 'string', 'max:50'],
        ]);

        $cart = Cart::where('user_id', $request->user()->id)
            ->with('items.variant.product.shop')
            ->firstOrFail();

        if ($cart->items->isEmpty()) {
            return response()->json(['message' => 'Your cart is empty.'], 422);
        }

        $order = $createOrder(
            cart: $cart,
            buyer: $request->user(),
            shippingAddress: $validated['shipping_address'],
            note: $validated['note'] ?? null,
        );

        $paymentMethod = $validated['payment_method'] ?? 'aba_khqr';

        // COD — no payment needed, mark order directly
        if ($paymentMethod === 'cod') {
            return response()->json([
                'order' => $order->load('subOrders'),
                'transaction_id' => null,
                'payment_data' => null,
            ], 201);
        }

        $paymentData = $initiatePayment($order);

        return response()->json([
            'order' => $order->load('subOrders'),
            'transaction_id' => $paymentData['transaction_id'],
            'payment_data' => $paymentData['form_data'],
        ], 201);
    }

    public function poll(string $transaction, PaymentGateway $gateway): JsonResponse
    {
        $payment = Payment::where('transaction_id', $transaction)->firstOrFail();

        if ($payment->status === 'paid') {
            return response()->json(['paid' => true, 'status' => 'paid', 'expired' => false]);
        }

        $expired = $payment->created_at !== null && $payment->created_at->addMinutes(15)->isPast();

        if ($payment->status === 'pending' && ! $expired) {
            $result = $gateway->checkPaymentStatus($transaction);

            if ($result['paid']) {
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

                event(new \App\Events\Payment\PaymentReceived($payment));

                return response()->json(['paid' => true, 'status' => 'paid', 'expired' => false]);
            }
        }

        return response()->json([
            'paid' => false,
            'status' => $payment->status,
            'expired' => $expired,
        ]);
    }
}
