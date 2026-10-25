<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 sm:px-6 lg:px-8">
            {{-- Metrics --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($metrics as $metric)
                    <div class="card">
                        <div class="card-header"
                            style="flex-direction:row;align-items:center;justify-content:space-between;padding-bottom:0.5rem;">
                            <p class="card-description"
                                style="font-weight:500;color:--foreground;font-size:0.8125rem;">
                                {{ $metric['label'] }}</p>
                            <i data-lucide="{{ $metric['icon'] }}" class="h-5 w-5"></i>
                        </div>
                        <div class="card-content" style="padding-top:0.25rem;">
                            <p
                                style="font-size:1.625rem;font-weight:700;margin:0;letter-spacing:-0.025em;">
                                {{ $metric['value'] }}
                            </p>
                            <p class="card-description {{ Str::contains($metric['description'], '+') ? 'text-green-500' : 'text-red-500' }}"
                                style="font-size:0.75rem;margin-top:0.25rem;">{{ $metric['description'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Charts --}}
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-5">
                {{-- Orders Analytic --}}
                <div class="card lg:col-span-3">
                    <div class="card-header flex flex-row items-start justify-between gap-4">
                        <div class="flex flex-col gap-2">
                            <h3 class="card-title text-lg">Orders Analytic</h3>
                            <div class="flex flex-wrap items-center gap-4 text-xs text-[--muted-foreground]">
                                <span class="flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full" style="background-color:#22c55e"></span>
                                    Income
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full" style="background-color:#8b5cf6"></span>
                                    Expense
                                </span>
                            </div>
                        </div>
                        <div class="relative shrink-0">
                            <i data-lucide="calendar"
                                class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-[--muted-foreground]"></i>
                            <select class="select w-auto pl-8 text-xs" data-size="sm" aria-label="Orders period">
                                <option value="this-year">This Year</option>
                                <option value="last-year">Last Year</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-content pt-4">
                        <div class="h-70">
                            <canvas id="orders-chart" role="img"
                                aria-label="Orders income and expense chart"></canvas>
                        </div>
                    </div>
                </div>

                {{-- Operating System --}}
                <div class="card lg:col-span-2">
                    <div class="card-header flex flex-row items-center justify-between gap-3">
                        <h3 class="card-title text-lg">Operating System</h3>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-[--muted-foreground]">Sort by</span>
                            <div class="relative shrink-0">
                                <i data-lucide="calendar"
                                    class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-[--muted-foreground]"></i>
                                <select class="select w-auto pl-8 text-xs" data-size="sm"
                                    aria-label="Operating system period">
                                    <option value="30-days">Last 30 days</option>
                                    <option value="7-days">Last 7 days</option>
                                    <option value="90-days">Last 90 days</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-content pt-4">
                        <div class="grid grid-cols-1 items-center gap-4 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                            <ul class="flex flex-col gap-3">
                                @foreach ($operating_systems as $operatingSystem)
                                    <li class="flex items-center gap-2 text-sm text-[--foreground]">
                                        <span class="h-2.5 w-2.5 shrink-0 rounded-full"
                                            style="background-color:{{ $operatingSystem['color'] }}"></span>
                                        <span>{{ $operatingSystem['name'] }} ({{ $operatingSystem['value'] }}%)</span>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="h-55 max-w-55 mx-auto w-full">
                                <canvas id="operating-system-chart" role="img"
                                    aria-label="Operating system distribution chart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Products Clicks --}}
            <div class="card divide-y divide-neutral-200">
                <div class="card-header flex flex-row flex-wrap items-center justify-between gap-3 pb-4">
                    <div>
                        <h3 class="card-title text-lg">Products Clicks</h3>
                        <p class="card-description">Engagement for the most viewed products this month.</p>
                    </div>
                    <span class="badge" data-variant="outline">Last 30 days</span>
                </div>
                <div class="card-content p-0">
                    <table class="table">
                        <thead>
                            <tr class="table-row">
                                <th class="table-head w-16 rounded-none">Image</th>
                                <th class="table-head w-full rounded-none">Name</th>
                                <th class="table-head rounded-none text-right">Last Month</th>
                                <th class="table-head rounded-none text-right">This Month</th>
                                <th class="table-head rounded-none text-right">Total Clicks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($products_clicks as $product)
                                @php
                                    $trend = $product['trend'];
                                    $trendUp = $trend > 0;
                                    $trendDown = $trend < 0;
                                    $trendClasses = $trendUp
                                        ? 'bg-emerald-50 text-emerald-700'
                                        : ($trendDown
                                            ? 'bg-red-50 text-red-600'
                                            : 'bg-[--muted] text-[--muted-foreground]');
                                    $trendIcon = $trendUp ? 'trending-up' : ($trendDown ? 'trending-down' : 'minus');
                                @endphp
                                <tr class="table-row">
                                    <td class="table-cell">
                                        <img src="{{ $product['image'] }}" alt="{{ $product['label'] }}"
                                            class="h-11 w-11 rounded-xl object-cover ring-1 ring-[--border]">
                                    </td>
                                    <td class="table-cell">
                                        <a href="{{ $product['url'] }}" class="font-medium hover:underline">
                                            {{ $product['label'] }}
                                        </a>
                                    </td>
                                    <td class="table-cell text-right tabular-nums text-[--muted-foreground]">
                                        {{ number_format($product['last_month_clicks'], 0, ',', '.') }}
                                    </td>
                                    <td class="table-cell text-right">
                                        <div class="inline-flex flex-col items-end gap-1">
                                            <span
                                                class="font-semibold tabular-nums">{{ number_format($product['this_month_clicks'], 0, ',', '.') }}</span>
                                            <span
                                                class="{{ $trendClasses }} inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium">
                                                <i data-lucide="{{ $trendIcon }}" class="h-3 w-3"></i>
                                                {{ $trendUp ? '+' : '' }}{{ number_format($trend, 1, ',', '.') }}%
                                            </span>
                                        </div>
                                    </td>
                                    <td class="table-cell text-right font-semibold tabular-nums">
                                        {{ number_format($product['total_clicks'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="table-cell py-8 text-center text-[--muted-foreground]">
                                        No product clicks recorded yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script type="application/json" id="dashboard-chart-data">@json($chart_data)</script>
    @push('scripts')
        @vite('resources/js/dashboard.js')
    @endpush
</x-app-layout>
