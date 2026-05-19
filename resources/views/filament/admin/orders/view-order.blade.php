<x-filament-panels::page>
    @php
        $order = $this->getRecord();
        $order->loadMissing(['buyer', 'payment', 'subOrders.items', 'subOrders.shop', 'subOrders.shipment']);
        $buyer   = $order->buyer;
        $payment = $order->payment;
        $address = $order->shipping_address ?? [];
        $fmt = fn (int $cents): string => '$' . number_format($cents / 100, 2);

        $card   = 'border:1px solid #e5e7eb; border-radius:16px; background:#fff; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,.06);';
        $title  = 'margin:0 0 16px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; color:#9ca3af;';
        $row    = 'display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #f3f4f6;';
        $rowLast= 'display:flex; justify-content:space-between; align-items:center; padding:8px 0;';
        $dt     = 'font-size:13px; color:#6b7280;';
        $dd     = 'font-size:13px; color:#111827; font-weight:500;';
    @endphp

    {{-- Timeline --}}
    @include('filament.vendor.orders.timeline', ['status' => $order->status])

    {{-- Info Grid --}}
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-top:24px;">

        {{-- Order Info --}}
        <div style="{{ $card }}">
            <h3 style="{{ $title }}">Order Information</h3>
            <div style="{{ $row }}">
                <span style="{{ $dt }}">Reference</span>
                <span style="{{ $dd }} font-family:ui-monospace,monospace; font-weight:700;">{{ $order->reference }}</span>
            </div>
            <div style="{{ $row }}">
                <span style="{{ $dt }}">Buyer</span>
                <span style="{{ $dd }} font-weight:600;">{{ $buyer?->name ?? 'N/A' }}</span>
            </div>
            <div style="{{ $row }}">
                <span style="{{ $dt }}">Email</span>
                <span style="{{ $dd }}">{{ $buyer?->email ?? 'N/A' }}</span>
            </div>
            <div style="{{ $row }}">
                <span style="{{ $dt }}">Date</span>
                <span style="{{ $dd }}">{{ $order->created_at->format('M d, Y H:i') }}</span>
            </div>
            <div style="{{ $row }}">
                <span style="{{ $dt }}">Payment</span>
                <span>
                    @if($payment && $payment->status === 'paid')
                        <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:99px; background:#dcfce7; color:#15803d; font-size:11px; font-weight:600;">
                            <svg style="width:12px; height:12px;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>
                            Paid &mdash; {{ $payment->provider }}
                        </span>
                    @else
                        <span style="padding:3px 10px; border-radius:99px; background:#fef3c7; color:#92400e; font-size:11px; font-weight:600;">Pending</span>
                    @endif
                </span>
            </div>
            <div style="{{ $rowLast }}">
                <span style="{{ $dt }}">Total</span>
                <span style="font-size:16px; font-weight:800; color:#111827;">{{ $fmt($order->total_cents) }}</span>
            </div>
        </div>

        {{-- Shipping Address --}}
        <div style="{{ $card }}">
            <h3 style="{{ $title }}">Shipping Address</h3>
            @if(!empty($address))
                <div style="font-weight:600; color:#111827; font-size:14px;">{{ $address['name'] ?? 'N/A' }}</div>
                <div style="color:#4b5563; font-size:13px; margin-top:4px;">{{ $address['phone'] ?? '' }}</div>
                <div style="color:#4b5563; font-size:13px; margin-top:2px;">{{ $address['address_line'] ?? $address['address'] ?? '' }}</div>
                <div style="color:#4b5563; font-size:13px; margin-top:2px;">{{ $address['city'] ?? '' }}{{ !empty($address['province']) ? ', ' . $address['province'] : '' }}</div>
            @else
                <div style="color:#9ca3af; font-size:13px;">No address provided.</div>
            @endif

            @if($order->note)
                <div style="border-top:1px solid #e5e7eb; margin-top:16px; padding-top:12px;">
                    <div style="{{ $dt }}; margin-bottom:4px;">Order Note</div>
                    <div style="font-size:13px; color:#374151;">{{ $order->note }}</div>
                </div>
            @endif
        </div>
    </div>

    {{-- Sub-Orders --}}
    @foreach($order->subOrders as $subOrder)
        <div style="border:1px solid #e5e7eb; border-radius:16px; background:#fff; margin-top:24px; box-shadow:0 1px 3px rgba(0,0,0,.06); overflow:hidden;">
            <div style="padding:16px 20px; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center; background:#f9fafb;">
                <div>
                    <span style="font-size:13px; font-weight:700; color:#111827;">{{ $subOrder->shop?->name ?? 'Shop #' . $subOrder->shop_id }}</span>
                    <span style="margin-left:8px; padding:3px 10px; border-radius:99px; font-size:11px; font-weight:600;
                        {{ match($subOrder->status) {
                            'completed', 'delivered' => 'background:#dcfce7; color:#15803d;',
                            'cancelled', 'refunded' => 'background:#fee2e2; color:#991b1b;',
                            'pending' => 'background:#f3f4f6; color:#4b5563;',
                            default => 'background:#dbeafe; color:#1e40af;',
                        } }}
                    ">{{ str_replace('_', ' ', ucfirst($subOrder->status)) }}</span>
                </div>
                <span style="font-size:13px; font-weight:700; color:#111827;">{{ $fmt($subOrder->subtotal_cents) }}</span>
            </div>

            @foreach($subOrder->items as $item)
                <div style="display:flex; align-items:center; gap:14px; padding:12px 20px; {{ !$loop->last ? 'border-bottom:1px solid #f3f4f6;' : '' }}">
                    <div style="width:48px; height:48px; flex-shrink:0; border-radius:8px; background:#f3f4f6; overflow:hidden;">
                        @if($item->image_snapshot)
                            <img src="{{ str_starts_with($item->image_snapshot, 'http') ? $item->image_snapshot : '/storage/' . $item->image_snapshot }}"
                                 style="width:100%; height:100%; object-fit:cover;" onerror="this.style.display='none'">
                        @endif
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:500; color:#111827; font-size:13px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $item->product_name_snapshot }}</div>
                        <div style="font-size:11px; color:#6b7280; margin-top:1px;">SKU: {{ $item->variant_sku_snapshot }}</div>
                    </div>
                    <div style="flex-shrink:0; text-align:right;">
                        <div style="font-size:12px; color:#6b7280;">{{ $item->quantity }} &times; {{ $fmt($item->unit_price_cents) }}</div>
                        <div style="font-weight:700; color:#111827; font-size:13px;">{{ $fmt($item->unit_price_cents * $item->quantity) }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach

    {{-- Grand Total --}}
    <div style="margin-top:24px; border:1px solid #e5e7eb; border-radius:16px; background:#f9fafb; padding:20px; display:flex; justify-content:space-between; align-items:center;">
        <span style="font-weight:700; color:#111827; font-size:15px;">Grand Total ({{ $order->subOrders->count() }} shop{{ $order->subOrders->count() > 1 ? 's' : '' }})</span>
        <span style="font-weight:800; color:#111827; font-size:22px;">{{ $fmt($order->total_cents) }}</span>
    </div>
</x-filament-panels::page>
