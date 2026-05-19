@php
    $steps = [
        ['key' => 'pending',    'label' => 'Order Placed'],
        ['key' => 'paid',       'label' => 'Paid'],
        ['key' => 'accepted',   'label' => 'Accepted'],
        ['key' => 'packed',     'label' => 'Packed'],
        ['key' => 'picked_up',  'label' => 'Picked Up'],
        ['key' => 'in_transit', 'label' => 'In Transit'],
        ['key' => 'delivered',  'label' => 'Delivered'],
        ['key' => 'completed',  'label' => 'Completed'],
    ];

    $terminalStatuses = ['cancelled', 'refunded', 'disputed', 'refund_requested'];
    $isTerminal = in_array($status, $terminalStatuses, true);

    $currentIdx = collect($steps)->search(fn ($s) => $s['key'] === $status);
    if ($currentIdx === false) $currentIdx = -1;
@endphp

<div style="border:1px solid #e5e7eb; border-radius:16px; background:#fff; padding:24px; box-shadow:0 1px 3px rgba(0,0,0,.06);">
    <h3 style="margin:0 0 20px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; color:#9ca3af;">Order Flow</h3>

    @if($isTerminal)
        <div style="display:flex; align-items:center; gap:12px; padding:16px; border-radius:12px; background:#fef2f2; border:1px solid #fecaca;">
            <div style="width:40px; height:40px; border-radius:50%; background:#fee2e2; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg style="width:20px; height:20px; color:#dc2626;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </div>
            <div>
                <div style="font-weight:600; color:#991b1b;">{{ str_replace('_', ' ', ucfirst($status)) }}</div>
                <div style="font-size:14px; color:#dc2626; margin-top:2px;">This order has been {{ str_replace('_', ' ', $status) }}.</div>
            </div>
        </div>
    @else
        <div style="display:flex; align-items:flex-start;">
            @foreach($steps as $i => $step)
                @php
                    $isCompleted = $i < $currentIdx;
                    $isCurrent   = $i === $currentIdx;
                @endphp
                <div style="flex:1; display:flex; flex-direction:column; align-items:center; text-align:center;">
                    <div style="display:flex; width:100%; align-items:center;">
                        @if($i > 0)
                            <div style="flex:1; height:3px; border-radius:2px; background:{{ ($isCompleted || $isCurrent) ? '#34d399' : '#e5e7eb' }};"></div>
                        @else
                            <div style="flex:1;"></div>
                        @endif
                        <div style="
                            width:36px; height:36px; border-radius:50%; flex-shrink:0;
                            display:flex; align-items:center; justify-content:center;
                            font-size:12px; font-weight:700;
                            {{ $isCompleted ? 'background:#10b981; color:#fff; box-shadow:0 2px 6px rgba(16,185,129,.3);' : '' }}
                            {{ $isCurrent ? 'background:#10b981; color:#fff; box-shadow:0 0 0 4px #d1fae5, 0 4px 12px rgba(16,185,129,.35);' : '' }}
                            {{ (!$isCompleted && !$isCurrent) ? 'background:#f3f4f6; color:#9ca3af; border:1.5px solid #e5e7eb;' : '' }}
                        ">
                            @if($isCompleted)
                                <svg style="width:16px; height:16px;" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            @else
                                {{ $i + 1 }}
                            @endif
                        </div>
                        @if($i < count($steps) - 1)
                            <div style="flex:1; height:3px; border-radius:2px; background:{{ $isCompleted ? '#34d399' : '#e5e7eb' }};"></div>
                        @else
                            <div style="flex:1;"></div>
                        @endif
                    </div>
                    <div style="
                        margin-top:8px; font-size:10px; line-height:1.2;
                        {{ $isCurrent ? 'font-weight:800; color:#047857; font-size:11px;' : ($isCompleted ? 'font-weight:600; color:#059669;' : 'font-weight:500; color:#9ca3af;') }}
                    ">{{ $step['label'] }}</div>
                </div>
            @endforeach
        </div>
    @endif
</div>
