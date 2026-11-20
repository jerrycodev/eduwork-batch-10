@php
    $navigationGroups = [
        [
            'label' => 'Workspace',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'dashboard', 'pattern' => 'dashboard', 'icon' => 'layout-dashboard'],
                ['label' => 'User management', 'route' => 'admin-users.index', 'pattern' => 'admin-users.*', 'icon' => 'users'],
            ],
        ],
        [
            'label' => 'Catalog',
            'items' => [
                ['label' => 'Products', 'route' => 'products.index', 'pattern' => 'products.*', 'icon' => 'package'],
                ['label' => 'Inventory', 'route' => 'inventory.index', 'pattern' => 'inventory.*', 'icon' => 'boxes'],
                ['label' => 'Categories', 'route' => 'product-categories.index', 'pattern' => 'product-categories.*', 'icon' => 'tags'],
                ['label' => 'Reviews', 'route' => 'reviews.index', 'pattern' => 'reviews.*', 'icon' => 'star'],
            ],
        ],
        [
            'label' => 'Sales & operations',
            'items' => [
                ['label' => 'Orders', 'route' => 'orders.index', 'pattern' => 'orders.*', 'icon' => 'shopping-bag'],
                ['label' => 'Payment audit', 'route' => 'payment-reconciliation.index', 'pattern' => 'payment-reconciliation.*', 'icon' => 'receipt-text'],
                ['label' => 'Biteship audit', 'route' => 'biteship-reconciliation.index', 'pattern' => 'biteship-reconciliation.*', 'icon' => 'truck'],
                ['label' => 'Email logs', 'route' => 'email-delivery-logs.index', 'pattern' => 'email-delivery-logs.*', 'icon' => 'mail'],
                ['label' => 'Admin activity', 'route' => 'activity-logs.index', 'pattern' => 'activity-logs.*', 'icon' => 'history'],
                ['label' => 'Customer activity', 'route' => 'customer-activity-logs.index', 'pattern' => 'customer-activity-logs.*', 'icon' => 'users'],
            ],
        ],
    ];
@endphp

<aside class="flex h-full w-72 shrink-0 flex-col border-r border-slate-800 bg-slate-950 text-slate-300">
    <div class="flex h-20 items-center gap-3 border-b border-white/10 px-6">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-500 text-white shadow-lg shadow-teal-950/40">
                <i data-lucide="store" class="h-5 w-5"></i>
            </span>
            <span class="min-w-0">
                <span class="block truncate text-sm font-bold tracking-wide text-white">{{ config('app.name', 'RevStore') }}</span>
                <span class="mt-0.5 block text-[10px] font-semibold uppercase tracking-[0.2em] text-teal-400">Admin workspace</span>
            </span>
        </a>
    </div>

    <nav class="flex-1 space-y-7 overflow-y-auto px-4 py-6" aria-label="Admin navigation">
        @foreach ($navigationGroups as $group)
            <div>
                <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">{{ $group['label'] }}</p>
                <div class="space-y-1">
                    @foreach ($group['items'] as $item)
                        @php($active = request()->routeIs($item['pattern']))
                        <a href="{{ route($item['route']) }}"
                            @if ($active) aria-current="page" @endif
                            class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ $active ? 'bg-teal-500/15 text-teal-300 ring-1 ring-inset ring-teal-400/20' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                            <i data-lucide="{{ $item['icon'] }}" class="h-[18px] w-[18px] shrink-0 {{ $active ? 'text-teal-300' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                            <span>{{ $item['label'] }}</span>
                            @if ($active)
                                <span class="ml-auto h-1.5 w-1.5 rounded-full bg-teal-400"></span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    <div class="border-t border-white/10 p-4">
        <div class="flex items-center gap-3 rounded-xl bg-white/5 p-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-teal-500/20 text-xs font-bold text-teal-300">
                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
            </span>
            <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-semibold text-white">{{ Auth::user()->name }}</span>
                <span class="block truncate text-xs text-slate-500">Administrator</span>
            </span>
        </div>
    </div>
</aside>
