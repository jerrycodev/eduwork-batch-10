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
                        <div class="card-header flex flex-row items-center justify-between pb-2">
                            <p class="card-description text-[0.8125rem] font-medium text-[--foreground]">
                                {{ $metric['label'] }}
                            </p>
                            <i data-lucide="{{ $metric['icon'] }}" class="h-5 w-5"></i>
                        </div>

                        <div class="card-content pt-1">
                            <p class="m-0 text-[1.625rem] font-bold tracking-tight">
                                {{ $metric['value'] }}
                            </p>

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
                            <h3 class="card-title text-lg">Orders This Year</h3>
                            <p class="card-description text-xs">Monthly order volume from database</p>
                        </div>

                    </div>
                    <div class="card-content pt-4">
                        <div class="h-70">
                            <canvas id="orders-chart" role="img"
                                aria-label="Monthly order totals from database"></canvas>
                        </div>
                    </div>
                </div>

                {{-- Order Status --}}
                <div class="card lg:col-span-2">
                    <div class="card-header flex flex-row items-center justify-between gap-3">
                        <div>
                            <h3 class="card-title text-lg">Order Status</h3>
                            <p class="card-description text-xs">Order totals grouped by current status</p>
                        </div>
                    </div>
                    <div class="card-content pt-4">
                        <div class="grid grid-cols-1 items-center gap-4 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                            <ul class="flex flex-col gap-3">
                                @foreach ($order_statuses as $orderStatus)
                                    <li class="flex items-center justify-between gap-2 text-sm text-[--foreground]">
                                        <span class="flex items-center gap-2">
                                            <span class="h-2.5 w-2.5 shrink-0 rounded-full"
                                                style="background-color:{{ $orderStatus['color'] }}"></span>
                                            {{ $orderStatus['name'] }}
                                        </span>
                                        <span class="font-semibold tabular-nums">{{ number_format($orderStatus['value'], 0, ',', '.') }}</span>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="h-55 max-w-55 mx-auto w-full">
                                <canvas id="order-status-chart" role="img"
                                    aria-label="Order totals grouped by status"></canvas>
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
                        <p class="card-description">Products ranked by total detail-page views.</p>
                    </div>
                    <span class="badge" data-variant="outline">All time</span>
                </div>
                <div class="card-content p-0">
                    <div class="w-full overflow-x-auto"><table class="table min-w-[520px]">
                        <thead>
                            <tr class="table-row">
                                <th class="table-head w-16 rounded-none">Image</th>
                                <th class="table-head w-full rounded-none">Product</th>
                                <th class="table-head rounded-none text-right">Total Clicks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($products_clicks as $product)
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
                                    <td class="table-cell text-right font-semibold tabular-nums">
                                        {{ number_format($product['clicks'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="table-cell py-8 text-center text-[--muted-foreground]">
                                        No products available yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table></div>
                </div>
            </div>
        </div>
    </div>

    <script type="application/json" id="dashboard-chart-data">@json($chart_data)</script>
    @push('scripts')
        @vite('resources/js/dashboard.js')
    @endpush
</x-app-layout>
