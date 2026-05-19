<x-filament-panels::page>
    @php
        $shop = App\Models\Shop::where('owner_id', auth()->id())->first();
    @endphp

    <form wire:submit="save">
        {{ $this->form }}

        {{-- Current branding previews --}}
        @if($shop?->logo || $shop?->banner)
        <div class="rounded-xl border bg-white p-5 shadow-sm">
            <p class="mb-3 text-sm font-semibold text-gray-700">Current Branding</p>
            <div class="flex flex-wrap gap-6">
                @if($shop->logo)
                <div>
                    <p class="mb-1 text-xs text-gray-500">Shop Logo</p>
                    <img src="{{ Storage::disk('public')->url($shop->logo) }}"
                         alt="Shop Logo"
                         class="h-20 w-20 rounded-xl border object-cover shadow-sm">
                </div>
                @endif
                @if($shop->banner)
                <div>
                    <p class="mb-1 text-xs text-gray-500">Shop Banner</p>
                    <img src="{{ Storage::disk('public')->url($shop->banner) }}"
                         alt="Shop Banner"
                         class="h-20 w-48 rounded-xl border object-cover shadow-sm">
                </div>
                @endif
            </div>
            <p class="mt-2 text-xs text-gray-400">Upload a new file above to replace these.</p>
        </div>
        @endif

        <div class="mt-16 pb-8">
            <x-filament::button type="submit" size="lg" icon="heroicon-o-check">
                Save Settings
            </x-filament::button>
        </div>
    </form>

    <x-filament-actions::modals />
</x-filament-panels::page>
