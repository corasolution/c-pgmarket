<x-filament-widgets::widget>
    @if(count($items) > 0)
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:12px;">
            @foreach($items as $item)
                <a href="{{ $item['url'] }}"
                   style="display:flex; align-items:center; gap:14px; padding:16px 18px; border-radius:14px; background:{{ $item['bg'] }}; border:1px solid {{ $item['border'] }}; text-decoration:none; transition:transform .15s;">
                    <div style="width:44px; height:44px; border-radius:12px; background:{{ $item['color'] }}15; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <span style="font-size:20px; font-weight:800; color:{{ $item['color'] }};">{{ $item['count'] }}</span>
                    </div>
                    <div>
                        <div style="font-size:13px; font-weight:600; color:#374151;">{{ $item['label'] }}</div>
                        <div style="font-size:11px; color:{{ $item['color'] }}; font-weight:500; margin-top:2px;">View &rarr;</div>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div style="text-align:center; padding:24px; color:#9ca3af; font-size:14px;">
            No pending actions — all caught up!
        </div>
    @endif
</x-filament-widgets::widget>
