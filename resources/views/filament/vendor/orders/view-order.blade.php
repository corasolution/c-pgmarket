<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $record->loadMissing(['order.buyer', 'order.payment', 'items', 'shipment']);
        $order   = $record->order;
        $buyer   = $order->buyer;
        $payment = $order->payment;
        $address = $order->shipping_address ?? [];
        $items   = $record->items;
        $shipment = $record->shipment;
        $fmt = fn (int $cents): string => '$' . number_format($cents / 100, 2);

        $card   = 'border:1px solid #e5e7eb; border-radius:16px; background:#fff; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,.06);';
        $title  = 'margin:0 0 16px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; color:#9ca3af;';
        $row    = 'display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #f3f4f6;';
        $rowLast= 'display:flex; justify-content:space-between; align-items:center; padding:8px 0;';
        $dt     = 'font-size:13px; color:#6b7280;';
        $dd     = 'font-size:13px; color:#111827; font-weight:500;';
    @endphp

    {{-- Timeline --}}
    @include('filament.vendor.orders.timeline', ['status' => $record->status])

    {{-- Info Grid --}}
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-top:24px;">

        {{-- Order Info --}}
        <div style="{{ $card }}">
            <h3 style="{{ $title }}">Order Information</h3>
            <div style="{{ $row }}">
                <span style="{{ $dt }}">Order Ref</span>
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
                <span style="{{ $dt }}">Order Date</span>
                <span style="{{ $dd }}">{{ $record->created_at->format('M d, Y H:i') }}</span>
            </div>
            <div style="{{ $row }}">
                <span style="{{ $dt }}">Payment</span>
                <span>
                    @if($payment && $payment->status === 'paid')
                        <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:99px; background:#dcfce7; color:#15803d; font-size:11px; font-weight:600;">
                            <svg style="width:12px; height:12px;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>
                            Paid
                        </span>
                    @else
                        <span style="padding:3px 10px; border-radius:99px; background:#fef3c7; color:#92400e; font-size:11px; font-weight:600;">Pending</span>
                    @endif
                </span>
            </div>
            <div style="{{ $rowLast }}">
                <span style="{{ $dt }}">Status</span>
                <span style="padding:3px 10px; border-radius:99px; font-size:11px; font-weight:600;
                    {{ match($record->status) {
                        'completed', 'delivered' => 'background:#dcfce7; color:#15803d;',
                        'cancelled', 'refunded', 'disputed' => 'background:#fee2e2; color:#991b1b;',
                        'pending' => 'background:#f3f4f6; color:#4b5563;',
                        default => 'background:#dbeafe; color:#1e40af;',
                    } }}
                ">{{ str_replace('_', ' ', ucfirst($record->status)) }}</span>
            </div>
            @if($record->vendor_note)
                <div style="border-top:1px solid #e5e7eb; margin-top:12px; padding-top:12px;">
                    <div style="{{ $dt }}; margin-bottom:4px;">Vendor Note</div>
                    <div style="font-size:13px; color:#374151;">{{ $record->vendor_note }}</div>
                </div>
            @endif
        </div>

        {{-- Shipping + Shipment --}}
        <div style="{{ $card }}">
            <h3 style="{{ $title }}">Shipping Address</h3>
            @if(!empty($address))
                <div style="font-weight:600; color:#111827; font-size:14px;">{{ $address['name'] ?? 'N/A' }}</div>
                <div style="color:#4b5563; font-size:13px; margin-top:4px;">{{ $address['phone'] ?? '' }}</div>
                <div style="color:#4b5563; font-size:13px; margin-top:2px;">{{ $address['address_line'] ?? $address['address'] ?? '' }}</div>
                <div style="color:#4b5563; font-size:13px; margin-top:2px;">{{ $address['city'] ?? '' }}{{ !empty($address['province']) ? ', ' . $address['province'] : '' }}</div>
            @else
                <div style="color:#9ca3af; font-size:13px;">No shipping address provided.</div>
            @endif

            <div style="border-top:1px solid #e5e7eb; margin-top:20px; padding-top:16px;">
                <h4 style="{{ $title }}">Shipment</h4>
                @if($shipment)
                    <div style="{{ $row }}">
                        <span style="{{ $dt }}">Provider</span>
                        <span style="{{ $dd }} font-weight:600;">{{ ucfirst($shipment->provider) }}</span>
                    </div>
                    <div style="{{ $row }}">
                        <span style="{{ $dt }}">Tracking #</span>
                        <span style="{{ $dd }} font-family:ui-monospace,monospace;">{{ $shipment->tracking_number ?? 'N/A' }}</span>
                    </div>
                    <div style="{{ $rowLast }}">
                        <span style="{{ $dt }}">Status</span>
                        <span style="{{ $dd }}">{{ ucfirst($shipment->status) }}</span>
                    </div>
                @else
                    <div style="border:2px dashed #d1d5db; border-radius:12px; background:#f9fafb; padding:20px; text-align:center;">
                        <svg style="width:28px; height:28px; color:#9ca3af; margin:0 auto 8px;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
                        <div style="font-size:12px; color:#6b7280;">Delivery API integration pending.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Order Items --}}
    <div style="border:1px solid #e5e7eb; border-radius:16px; background:#fff; margin-top:24px; box-shadow:0 1px 3px rgba(0,0,0,.06); overflow:hidden;">
        <div style="padding:16px 20px; border-bottom:1px solid #e5e7eb;">
            <h3 style="margin:0; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; color:#9ca3af;">
                Order Items ({{ $items->count() }})
            </h3>
        </div>
        @foreach($items as $item)
            <div style="display:flex; align-items:center; gap:16px; padding:14px 20px; {{ !$loop->last ? 'border-bottom:1px solid #f3f4f6;' : '' }}">
                <div style="width:52px; height:52px; flex-shrink:0; border-radius:10px; background:#f3f4f6; overflow:hidden; display:flex; align-items:center; justify-content:center;">
                    @if($item->image_snapshot)
                        <img src="{{ str_starts_with($item->image_snapshot, 'http') ? $item->image_snapshot : '/storage/' . $item->image_snapshot }}"
                             alt="{{ $item->product_name_snapshot }}"
                             style="width:100%; height:100%; object-fit:cover;"
                             onerror="this.style.display='none'"
                        >
                    @else
                        <svg style="width:22px; height:22px; color:#cbd5e1;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                    @endif
                </div>
                <div style="flex:1; min-width:0;">
                    <div style="font-weight:600; color:#111827; font-size:13px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $item->product_name_snapshot }}</div>
                    <div style="font-size:11px; color:#6b7280; margin-top:2px;">
                        SKU: {{ $item->variant_sku_snapshot }}
                        @if($item->options_snapshot && count($item->options_snapshot))
                            &middot; {{ implode(', ', array_values($item->options_snapshot)) }}
                        @endif
                    </div>
                </div>
                <div style="flex-shrink:0; text-align:right;">
                    <div style="font-size:12px; color:#6b7280;">{{ $item->quantity }} &times; {{ $fmt($item->unit_price_cents) }}</div>
                    <div style="font-weight:700; color:#111827; font-size:14px; margin-top:2px;">{{ $fmt($item->unit_price_cents * $item->quantity) }}</div>
                </div>
            </div>
        @endforeach

        {{-- Summary --}}
        <div style="padding:16px 20px; border-top:1px solid #e5e7eb; background:#f9fafb;">
            <div style="display:flex; justify-content:space-between; font-size:13px;">
                <span style="color:#6b7280;">Subtotal</span>
                <span style="font-weight:600; color:#111827;">{{ $fmt($record->subtotal_cents) }}</span>
            </div>
            <div style="display:flex; justify-content:space-between; font-size:13px; margin-top:6px;">
                <span style="color:#6b7280;">Shipping</span>
                <span style="font-weight:600; color:#111827;">{{ $record->shipping_fee_cents > 0 ? $fmt($record->shipping_fee_cents) : 'Free' }}</span>
            </div>
            <div style="display:flex; justify-content:space-between; margin-top:10px; padding-top:10px; border-top:1px solid #e5e7eb;">
                <span style="font-weight:700; color:#111827; font-size:14px;">Total</span>
                <span style="font-weight:800; color:#111827; font-size:18px;">{{ $fmt($record->subtotal_cents + $record->shipping_fee_cents) }}</span>
            </div>
        </div>
    </div>
</x-filament-panels::page>
