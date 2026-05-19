<x-filament-panels::page>
    @php
        $shop = App\Models\Shop::where('owner_id', auth()->id())->first();
        $verification = $shop?->verification;
        $status = $verification?->status;
    @endphp

    {{-- Status Banner --}}
    @if($status)
    <div style="border-radius: 12px; padding: 16px 20px; margin-bottom: 20px;
        @if($status === 'approved') background: #ecfdf5; border: 1px solid #a7f3d0;
        @elseif($status === 'rejected') background: #fef2f2; border: 1px solid #fecaca;
        @else background: #fffbeb; border: 1px solid #fde68a;
        @endif
    ">
        <div style="display: flex; align-items: center; gap: 12px;">
            @if($status === 'approved')
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <p style="font-weight: 600; color: #065f46; margin: 0;">Verification Approved</p>
                    <p style="font-size: 13px; color: #047857; margin: 4px 0 0;">
                        Your KYC documents have been verified.
                        @if($verification->reviewed_at) Reviewed on {{ $verification->reviewed_at->format('M d, Y') }}.@endif
                    </p>
                </div>
            @elseif($status === 'rejected')
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="#dc2626" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <p style="font-weight: 600; color: #991b1b; margin: 0;">Verification Rejected</p>
                    @if($verification->rejection_reason)
                    <p style="font-size: 13px; color: #b91c1c; margin: 4px 0 0;">
                        Reason: {{ $verification->rejection_reason }}
                    </p>
                    @endif
                    <p style="font-size: 13px; color: #6b7280; margin: 4px 0 0;">Please update your documents and re-submit.</p>
                </div>
            @else
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="#d97706" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <p style="font-weight: 600; color: #92400e; margin: 0;">Verification Pending</p>
                    <p style="font-size: 13px; color: #a16207; margin: 4px 0 0;">Your documents are under review. This usually takes 1-3 business days.</p>
                </div>
            @endif
        </div>
    </div>
    @endif

    <form wire:submit="save">
        {{ $this->form }}

        {{-- Existing document previews --}}
        @if($verification && ($verification->business_license || $verification->owner_id_front || $verification->owner_id_back))
        <div style="border-radius: 12px; border: 1px solid #e5e7eb; background: #fff; padding: 20px; margin-top: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.06);">
            <p style="font-weight: 600; color: #374151; margin: 0 0 12px; font-size: 14px;">Current Uploaded Documents</p>
            <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                @if($verification->business_license)
                <div>
                    <p style="font-size: 12px; color: #6b7280; margin: 0 0 6px;">Business License</p>
                    @if(str_ends_with($verification->business_license, '.pdf'))
                        <a href="{{ Storage::disk('public')->url($verification->business_license) }}" target="_blank"
                           style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 8px; border: 1px solid #e5e7eb; color: #4b5563; font-size: 13px; text-decoration: none;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                            View PDF
                        </a>
                    @else
                        <img src="{{ Storage::disk('public')->url($verification->business_license) }}" alt="Business License"
                             style="height: 80px; width: auto; border-radius: 8px; border: 1px solid #e5e7eb; object-fit: cover;">
                    @endif
                </div>
                @endif
                @if($verification->owner_id_front)
                <div>
                    <p style="font-size: 12px; color: #6b7280; margin: 0 0 6px;">ID Front</p>
                    <img src="{{ Storage::disk('public')->url($verification->owner_id_front) }}" alt="ID Front"
                         style="height: 80px; width: auto; border-radius: 8px; border: 1px solid #e5e7eb; object-fit: cover;">
                </div>
                @endif
                @if($verification->owner_id_back)
                <div>
                    <p style="font-size: 12px; color: #6b7280; margin: 0 0 6px;">ID Back</p>
                    <img src="{{ Storage::disk('public')->url($verification->owner_id_back) }}" alt="ID Back"
                         style="height: 80px; width: auto; border-radius: 8px; border: 1px solid #e5e7eb; object-fit: cover;">
                </div>
                @endif
            </div>
        </div>
        @endif

        @if($status !== 'approved')
        <div style="margin-top: 40px; padding-bottom: 24px;">
            <x-filament::button type="submit" size="lg" icon="heroicon-o-arrow-up-tray">
                {{ $verification ? 'Re-submit Verification' : 'Submit Verification' }}
            </x-filament::button>
        </div>
        @endif
    </form>

    <x-filament-actions::modals />
</x-filament-panels::page>
