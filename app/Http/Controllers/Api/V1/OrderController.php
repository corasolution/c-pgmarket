<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->where('buyer_id', $request->user()->id)
            ->with(['subOrders.items', 'payment'])
            ->latest()
            ->paginate(20);

        return response()->json($orders);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        if ($order->buyer_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json($order->load(['subOrders.items', 'subOrders.shipment', 'payment']));
    }

    public function store(Request $request): JsonResponse
    {
        // Delegate to CreateOrder action — placeholder until cart/checkout flow is wired
        abort(501, 'Use the storefront checkout flow.');
    }

    public function cancel(Request $request, Order $order): JsonResponse
    {
        if ($order->buyer_id !== $request->user()->id) {
            abort(403);
        }

        if (! in_array($order->status, ['pending', 'paid'])) {
            return response()->json(['message' => 'This order cannot be cancelled.'], 422);
        }

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $order->update([
            'status' => 'cancelled',
            'cancel_reason' => $request->reason,
        ]);

        // Cancel all sub-orders
        $order->subOrders()->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Order cancelled.']);
    }
}
