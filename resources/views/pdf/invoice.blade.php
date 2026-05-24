<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; line-height: 1.5; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .brand { font-size: 22px; font-weight: bold; color: #4f46e5; }
        .brand-sub { font-size: 11px; color: #6b7280; }
        .invoice-title { font-size: 18px; font-weight: bold; color: #111; margin-bottom: 5px; }
        .meta { font-size: 11px; color: #6b7280; margin-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #f3f4f6; padding: 8px 10px; text-align: left; font-size: 11px; font-weight: 600; color: #374151; border-bottom: 2px solid #e5e7eb; }
        td { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
        .text-right { text-align: right; }
        .summary { margin-top: 20px; float: right; width: 250px; }
        .summary-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 12px; }
        .summary-total { font-weight: bold; font-size: 14px; border-top: 2px solid #333; padding-top: 6px; margin-top: 4px; }
        .section { margin-top: 25px; }
        .section-title { font-size: 13px; font-weight: bold; color: #374151; margin-bottom: 8px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        .address { font-size: 11px; color: #4b5563; line-height: 1.6; }
        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 15px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; }
        .badge-paid { background: #dcfce7; color: #166534; }
        .badge-pending { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
    {{-- Header --}}
    <table style="margin-bottom: 30px;">
        <tr>
            <td style="border: none; padding: 0; width: 50%;">
                <div class="brand">PG Market</div>
                <div class="brand-sub">Cambodia's Multi-Vendor Marketplace</div>
            </td>
            <td style="border: none; padding: 0; text-align: right;">
                <div class="invoice-title">INVOICE</div>
                <div class="meta">Order: {{ $order->reference }}</div>
                <div class="meta">Date: {{ $order->created_at->format('M d, Y') }}</div>
                <div class="meta">
                    Status:
                    <span class="badge {{ $order->status === 'paid' || $order->status === 'completed' ? 'badge-paid' : 'badge-pending' }}">
                        {{ strtoupper(str_replace('_', ' ', $order->status)) }}
                    </span>
                </div>
            </td>
        </tr>
    </table>

    {{-- Bill To & Payment --}}
    <table style="margin-bottom: 20px;">
        <tr>
            <td style="border: none; padding: 0; width: 50%; vertical-align: top;">
                <div class="section-title">Bill To</div>
                <div class="address">
                    {{ $order->buyer?->name ?? 'Customer' }}<br>
                    {{ $order->buyer?->email ?? '' }}
                </div>
            </td>
            <td style="border: none; padding: 0; width: 50%; vertical-align: top;">
                <div class="section-title">Ship To</div>
                <div class="address">
                    @if($order->shipping_address)
                        {{ $order->shipping_address['name'] ?? '' }}<br>
                        {{ $order->shipping_address['phone'] ?? '' }}<br>
                        {{ $order->shipping_address['address_line'] ?? $order->shipping_address['address'] ?? '' }}<br>
                        {{ $order->shipping_address['city'] ?? '' }}{{ !empty($order->shipping_address['province']) ? ', ' . $order->shipping_address['province'] : '' }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- Items by Shop --}}
    @foreach($order->subOrders as $subOrder)
        <div class="section">
            <div class="section-title">{{ $subOrder->shop?->name ?? 'Vendor' }}</div>
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>SKU</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subOrder->items as $item)
                        <tr>
                            <td>{{ $item->product_name_snapshot }}</td>
                            <td>{{ $item->variant_sku_snapshot }}</td>
                            <td class="text-right">{{ $item->quantity }}</td>
                            <td class="text-right">${{ number_format($item->unit_price_cents / 100, 2) }}</td>
                            <td class="text-right">${{ number_format($item->unit_price_cents * $item->quantity / 100, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="4" class="text-right" style="font-weight: bold;">Shop Subtotal:</td>
                        <td class="text-right" style="font-weight: bold;">${{ number_format($subOrder->subtotal_cents / 100, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endforeach

    {{-- Grand Total --}}
    <table style="margin-top: 20px;">
        <tr>
            <td style="border: none; padding: 0; width: 60%;"></td>
            <td style="border: none; padding: 0;">
                <table>
                    <tr>
                        <td style="font-weight: bold; font-size: 14px; border-bottom: 2px solid #333; padding: 8px 10px;">Grand Total</td>
                        <td class="text-right" style="font-weight: bold; font-size: 14px; border-bottom: 2px solid #333; padding: 8px 10px;">
                            ${{ number_format($order->total_cents / 100, 2) }} {{ $order->total_currency }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Footer --}}
    <div class="footer">
        <p>Thank you for shopping on PG Market!</p>
        <p>&copy; {{ date('Y') }} PG Market. All rights reserved.</p>
    </div>
</body>
</html>
