<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Actions\Order\CancelOrder;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $orders = Order::where('buyer_id', $request->user()->id)
            ->with('subOrders')
            ->latest()
            ->get();

        return Inertia::render('storefront/orders/index', ['orders' => $orders]);
    }

    public function show(Request $request, Order $order): Response
    {
        abort_unless($order->buyer_id === $request->user()->id, 403);

        $order->load('subOrders.items');

        return Inertia::render('storefront/orders/show', ['order' => $order]);
    }

    public function cancel(Request $request, Order $order, CancelOrder $cancelOrder): RedirectResponse
    {
        abort_unless($order->buyer_id === $request->user()->id, 403);

        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $cancelOrder($order, $request->user(), $request->input('reason', ''));
        } catch (\Throwable $e) {
            return back()->withErrors(['cancel' => $e->getMessage()]);
        }

        return back()->with('success', 'Order has been cancelled.');
    }
}
