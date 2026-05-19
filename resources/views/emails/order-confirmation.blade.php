<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; color: #333; line-height: 1.6; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #e85d04; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
        .content { background: #f9fafb; padding: 20px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px; }
        .item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
        .total { font-size: 18px; font-weight: bold; margin-top: 12px; text-align: right; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #9ca3af; }
        a { color: #e85d04; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin:0; font-size:20px;">Order Confirmed!</h1>
        <p style="margin:4px 0 0; opacity:0.9;">Thank you for your order on PG Market</p>
    </div>

    <div class="content">
        <p>Hi {{ $order->buyer?->name ?? 'Customer' }},</p>
        <p>Your order <strong>{{ $order->reference }}</strong> has been placed successfully.</p>

        <h3 style="margin-top:20px;">Order Summary</h3>
        @foreach($order->subOrders as $subOrder)
            <p style="font-size:13px; color:#6b7280; margin-bottom:4px;">
                Shop: <strong>{{ $subOrder->shop?->name ?? 'Vendor' }}</strong>
            </p>
            @foreach($subOrder->items as $item)
                <div class="item">
                    <span>{{ $item->product_name_snapshot }} × {{ $item->quantity }}</span>
                    <span>${{ number_format($item->unit_price_cents * $item->quantity / 100, 2) }}</span>
                </div>
            @endforeach
        @endforeach

        <div class="total">
            Total: ${{ number_format($order->total_cents / 100, 2) }}
        </div>

        @if($order->shipping_address)
            <h3 style="margin-top:20px;">Shipping To</h3>
            <p style="font-size:14px;">
                {{ $order->shipping_address['name'] ?? '' }}<br>
                {{ $order->shipping_address['phone'] ?? '' }}<br>
                {{ $order->shipping_address['address_line'] ?? $order->shipping_address['address'] ?? '' }}<br>
                {{ $order->shipping_address['city'] ?? '' }}{{ !empty($order->shipping_address['province']) ? ', ' . $order->shipping_address['province'] : '' }}
            </p>
        @endif

        <p style="margin-top:20px;">
            <a href="{{ url('/orders/' . $order->id) }}">View your order →</a>
        </p>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} PG Market. All rights reserved.</p>
    </div>
</body>
</html>
