<x-filament-panels::page>
    @php
        $data = $this->getViewData();
        $shop = $data['shop'];
        $user = $data['user'];
    @endphp

    {{-- Shop hero banner --}}
    <div class="relative overflow-hidden rounded-2xl bg-linear-to-br from-emerald-600 via-emerald-500 to-teal-400 p-6 text-white shadow-lg">
        <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
        <div class="absolute -bottom-8 left-1/3 h-32 w-32 rounded-full bg-white/10 blur-2xl"></div>
        <div class="relative flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-100">Welcome back,</p>
                <h1 class="text-2xl font-extrabold tracking-tight">{{ $user?->name }}</h1>
                @if($shop)
                    <div class="mt-1 flex items-center gap-2">
                        <span class="text-base font-semibold text-white/90">{{ $shop->name }}</span>
                        <span class="rounded-full bg-white/20 px-2.5 py-0.5 text-xs font-medium capitalize backdrop-blur-sm">
                            {{ $shop->status }}
                        </span>
                    </div>
                @endif
            </div>
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/20 text-4xl font-black text-white shadow-inner backdrop-blur-sm">
                {{ strtoupper(substr($user?->name ?? 'V', 0, 1)) }}
            </div>
        </div>
    </div>

    {{-- Wallet stats --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        {{-- Available Balance --}}
        <div class="relative overflow-hidden rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-500">Available Balance</span>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <x-heroicon-o-banknotes class="h-5 w-5" />
                </div>
            </div>
            <p class="mt-2 text-3xl font-extrabold text-gray-900">${{ $data['available'] }}</p>
            <p class="mt-1 text-xs font-medium text-emerald-600">✓ Ready to withdraw</p>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-linear-to-r from-emerald-400 to-teal-400 rounded-b-2xl"></div>
        </div>

        {{-- Pending Balance --}}
        <div class="relative overflow-hidden rounded-2xl border border-amber-100 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-500">Pending Balance</span>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-500">
                    <x-heroicon-o-clock class="h-5 w-5" />
                </div>
            </div>
            <p class="mt-2 text-3xl font-extrabold text-gray-900">${{ $data['pending'] }}</p>
            <p class="mt-1 text-xs font-medium text-amber-500">⏳ In escrow (7-day hold)</p>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-linear-to-r from-amber-400 to-orange-300 rounded-b-2xl"></div>
        </div>

        {{-- Lifetime Earned --}}
        <div class="relative overflow-hidden rounded-2xl border border-blue-100 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-500">Lifetime Earned</span>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <x-heroicon-o-chart-bar class="h-5 w-5" />
                </div>
            </div>
            <p class="mt-2 text-3xl font-extrabold text-gray-900">${{ $data['lifetime'] }}</p>
            <p class="mt-1 text-xs font-medium text-blue-500">↑ Total earnings to date</p>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-linear-to-r from-blue-400 to-indigo-400 rounded-b-2xl"></div>
        </div>
    </div>

    {{-- Activity stats --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="flex items-center gap-4 rounded-2xl border bg-white p-4 shadow-sm">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                <x-heroicon-o-shopping-bag class="h-6 w-6" />
            </div>
            <div>
                <p class="text-2xl font-extrabold text-gray-900">{{ $data['totalProducts'] }}</p>
                <p class="text-xs text-gray-500">Active Products</p>
            </div>
        </div>

        <div class="flex items-center gap-4 rounded-2xl border bg-white p-4 shadow-sm">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                <x-heroicon-o-clipboard-document-list class="h-6 w-6" />
            </div>
            <div>
                <p class="text-2xl font-extrabold text-gray-900">{{ $data['totalOrders'] }}</p>
                <p class="text-xs text-gray-500">Total Orders</p>
            </div>
        </div>

        <div class="flex items-center gap-4 rounded-2xl border bg-white p-4 shadow-sm">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-500">
                <x-heroicon-o-bell-alert class="h-6 w-6" />
            </div>
            <div>
                <p class="text-2xl font-extrabold text-gray-900">{{ $data['pendingOrders'] }}</p>
                <p class="text-xs text-gray-500">Pending Orders</p>
            </div>
        </div>
    </div>

    {{-- Quick actions --}}
    <div class="rounded-2xl border bg-white p-5 shadow-sm">
        <h2 class="mb-4 text-sm font-semibold text-gray-700">Quick Actions</h2>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('filament.vendor.resources.products.create') }}"
               class="flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                <x-heroicon-o-plus class="h-4 w-4" />
                Add Product
            </a>
            <a href="{{ route('filament.vendor.resources.orders.index') }}"
               class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                <x-heroicon-o-clipboard-document-list class="h-4 w-4" />
                View Orders
            </a>
            <a href="{{ route('filament.vendor.resources.payouts.index') }}"
               class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                <x-heroicon-o-banknotes class="h-4 w-4" />
                Request Payout
            </a>
            <a href="{{ route('filament.vendor.pages.shop-settings') }}"
               class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                <x-heroicon-o-cog-6-tooth class="h-4 w-4" />
                Shop Settings
            </a>
        </div>
    </div>

    {{-- Recent orders --}}
    <div class="rounded-2xl border bg-white shadow-sm">
        <div class="flex items-center justify-between border-b px-5 py-4">
            <h2 class="text-sm font-semibold text-gray-700">Recent Orders</h2>
            <a href="{{ route('filament.vendor.resources.orders.index') }}"
               class="text-xs font-medium text-emerald-600 hover:underline">View all →</a>
        </div>
        @if($data['recentOrders']->isEmpty())
            <div class="py-12 text-center text-sm text-gray-400">
                <x-heroicon-o-clipboard-document-list class="mx-auto mb-2 h-8 w-8 opacity-30" />
                No orders yet.
            </div>
        @else
            <ul class="divide-y">
                @foreach($data['recentOrders'] as $subOrder)
                    <li class="flex items-center justify-between px-5 py-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">
                                Order #{{ $subOrder->order?->reference ?? $subOrder->order_id }}
                            </p>
                            <p class="text-xs text-gray-400">
                                {{ $subOrder->items?->count() ?? 0 }} item(s) ·
                                {{ $subOrder->created_at?->diffForHumans() }}
                            </p>
                        </div>
                        <span @class([
                            'rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize',
                            'bg-yellow-100 text-yellow-700' => $subOrder->status === 'pending',
                            'bg-blue-100 text-blue-700'    => in_array($subOrder->status, ['paid', 'accepted', 'packed']),
                            'bg-emerald-100 text-emerald-700' => in_array($subOrder->status, ['delivered', 'completed']),
                            'bg-red-100 text-red-700'      => in_array($subOrder->status, ['cancelled', 'refunded']),
                            'bg-gray-100 text-gray-600'    => true,
                        ])>
                            {{ str_replace('_', ' ', $subOrder->status) }}
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-filament-panels::page>
