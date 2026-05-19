<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Actions\Order\CreateOrder;
use App\Actions\Payment\InitiatePayment;
use App\Contracts\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\CheckoutRequest;
use App\Models\Cart;
use App\Models\Payment;
use App\Models\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CheckoutController extends Controller
{
    public function index(Request $request): Response
    {
        $cart = Cart::where('user_id', $request->user()->id)
            ->with('items.variant.product')
            ->firstOrFail();

        $addresses = $request->user()
            ->addresses()
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('storefront/checkout', [
            'cart' => $cart,
            'addresses' => $addresses,
        ]);
    }

    /**
     * Create the order then build ABA PayWay popup checkout form data.
     * Returns signed form fields so the frontend can render the hidden form + auto-open popup.
     */
    public function store(
        CheckoutRequest $request,
        CreateOrder $createOrder,
        InitiatePayment $initiatePayment,
    ): Response|RedirectResponse {
        $cart = Cart::where('user_id', $request->user()->id)
            ->with('items.variant.product.shop')
            ->firstOrFail();

        if ($cart->items->isEmpty()) {
            return back()->withErrors(['cart' => 'Your cart is empty.']);
        }

        $order = $createOrder(
            cart: $cart,
            buyer: $request->user(),
            shippingAddress: $request->validated('shipping_address'),
            note: $request->validated('note'),
        );
        $paymentData = $initiatePayment($order);

        return Inertia::render('storefront/checkout-qr', [
            'order' => $order->load('subOrders'),
            'orderReference' => $order->reference,
            'paymentData' => $paymentData['form_data'],
        ]);
    }

    /**
     * Frontend polls this every 10 s while the popup is displayed.
     * Also re-confirms with ABA if payment is still pending and not expired.
     */
    public function poll(string $transaction, PaymentGateway $gateway): JsonResponse
    {
        $payment = Payment::where('transaction_id', $transaction)->firstOrFail();

        // Fast path: already paid
        if ($payment->status === 'paid') {
            return response()->json(['paid' => true, 'status' => 'paid', 'expired' => false]);
        }

        // Expire after 15 minutes (matches ABA lifetime param)
        $expired = $payment->created_at && $payment->created_at->addMinutes(15)->isPast();

        // Re-confirm with ABA if still pending
        if ($payment->status === 'pending' && ! $expired) {
            $result = $gateway->checkPaymentStatus($transaction);

            if ($result['paid']) {
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

                // Fire event to credit vendor wallets
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
