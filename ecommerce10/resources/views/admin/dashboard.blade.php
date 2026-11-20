{{-- Dokumentasi: Ringkasan metrik operasional dan grafik performa toko untuk administrator. --}}
<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-gray-900">{{ __('Dashboard') }}</h2>
            <p class="mt-1 text-sm text-gray-500">Overview of your store performance and activity.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="flex w-full flex-col gap-6 px-4 sm:px-6 lg:px-8">
            {{-- Date filters --}}
            <section class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Filter Dashboard</h3>
                    <p class="mt-1 text-sm text-gray-500">Showing analytics for {{ $filter['label'] }}.</p>
                </div>
                <form method="GET" action="{{ route('dashboard') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div>
                        <label for="dashboard-period" class="mb-1.5 block text-xs font-medium text-gray-600">Filter by</label>
                        <select id="dashboard-period" name="period" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:w-36">
                            <option value="day" @selected($filter['period'] === 'day')>Day</option>
                            <option value="month" @selected($filter['period'] === 'month')>Month</option>
                            <option value="year" @selected($filter['period'] === 'year')>Year</option>
                        </select>
                    </div>
                    <div>
                        <label for="dashboard-date" class="mb-1.5 block text-xs font-medium text-gray-600">Select {{ ucfirst($filter['period']) }}</label>
                        <input
                            id="dashboard-date"
                            name="date"
                            type="{{ $filter['period'] === 'day' ? 'date' : ($filter['period'] === 'month' ? 'month' : 'number') }}"
                            value="{{ $filter['date'] }}"
                            @if ($filter['period'] === 'year') min="2000" max="2100" placeholder="YYYY" @endif
                            required
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:w-44"
                        >
                    </div>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">
                        <i data-lucide="filter" class="h-4 w-4"></i> Apply Filter
                    </button>
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Reset</a>
                </form>
            </section>

            {{-- Summary metrics --}}
            <section aria-label="Store summary" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($metrics as $metric)
                    <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-primary-200 hover:shadow-md">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-medium text-gray-500">{{ $metric['label'] }}</p>
                            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                                <i data-lucide="{{ $metric['icon'] }}" class="h-5 w-5"></i>
                            </span>
                        </div>
                        <p class="mt-4 text-3xl font-bold tracking-tight text-gray-900 tabular-nums">
                            {{ number_format($metric['value'], 0, ',', '.') }}
                        </p>
                    </article>
                @endforeach
            </section>

            {{-- Analytics --}}
            <section aria-label="Store analytics" class="grid grid-cols-1 gap-5 xl:grid-cols-5">
                <article class="min-w-0 rounded-xl border border-gray-200 bg-white p-5 shadow-sm xl:col-span-3">
                    <div class="mb-5">
                        <h3 class="text-base font-semibold text-gray-900">Orders {{ $filter['period'] === 'day' ? 'Today' : ($filter['period'] === 'month' ? 'This Month' : 'This Year') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ $filter['period'] === 'day' ? 'Hourly' : ($filter['period'] === 'month' ? 'Daily' : 'Monthly') }} order volume for {{ $filter['label'] }}</p>
                    </div>
                    <div class="relative h-64 sm:h-72">
                        <canvas id="orders-chart" role="img" aria-label="Monthly order totals from database"></canvas>
                    </div>
                </article>

                <article class="min-w-0 rounded-xl border border-gray-200 bg-white p-5 shadow-sm xl:col-span-2">
                    <div class="mb-5">
                        <h3 class="text-base font-semibold text-gray-900">Order Status</h3>
                        <p class="mt-1 text-sm text-gray-500">Order totals grouped by status for {{ $filter['label'] }}</p>
                    </div>
                    <div class="grid grid-cols-1 items-center gap-6 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                        <ul class="flex flex-col gap-3" aria-label="Order status totals">
                            @foreach ($order_statuses as $orderStatus)
                                <li class="flex items-center justify-between gap-3 text-sm">
                                    <span class="flex min-w-0 items-center gap-2.5 text-gray-600">
                                        <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $orderStatus['color'] }}"></span>
                                        <span>{{ $orderStatus['name'] }}</span>
                                    </span>
                                    <span class="font-semibold tabular-nums text-gray-900">{{ number_format($orderStatus['value'], 0, ',', '.') }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <div class="mx-auto h-52 w-full max-w-52">
                            <canvas id="order-status-chart" role="img" aria-label="Order totals grouped by status"></canvas>
                        </div>
                    </div>
                </article>
            </section>

            {{-- Latest orders --}}
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex flex-col gap-1 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Latest Orders</h3>
                        <p class="mt-1 text-sm text-gray-500">The five most recent orders for {{ $filter['label'] }}.</p>
                    </div>
                    <a href="{{ route('orders.index') }}" class="inline-flex w-fit items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:border-primary-200 hover:bg-primary-50 hover:text-primary-700">
                        View all orders <i data-lucide="arrow-up-right" class="h-4 w-4"></i>
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th scope="col" class="px-5 py-3.5 font-semibold">Order</th>
                                <th scope="col" class="px-5 py-3.5 font-semibold">Customer</th>
                                <th scope="col" class="px-5 py-3.5 font-semibold">Date</th>
                                <th scope="col" class="px-5 py-3.5 text-right font-semibold">Total</th>
                                <th scope="col" class="px-5 py-3.5 font-semibold">Order status</th>
                                <th scope="col" class="px-5 py-3.5 font-semibold">Payment</th>
                                <th scope="col" class="px-5 py-3.5 text-right font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($latest_orders as $order)
                                <tr class="transition hover:bg-gray-50/80">
                                    <td class="whitespace-nowrap px-5 py-4 font-semibold text-gray-900">{{ $order->order_number }}</td>
                                    <td class="px-5 py-4">
                                        <p class="font-medium text-gray-900">{{ $order->user_name }}</p>
                                        <p class="mt-0.5 text-xs text-gray-500">{{ $order->user_email }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-gray-600">{{ $order->created_at->format('d M Y, H:i') }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right font-semibold text-gray-900">Rp {{ number_format($order->total, 0, ',', '.') }}</td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ in_array($order->status, ['completed', 'success'], true) ? 'bg-green-50 text-green-700' : (in_array($order->status, ['shipped', 'processing'], true) ? 'bg-blue-50 text-blue-700' : ($order->status === 'delivered' ? 'bg-cyan-50 text-cyan-700' : ($order->status === 'cancelled' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700'))) }}">
                                            {{ str($order->status)->replace('_', ' ')->title() }}
                                        </span>
                                        @if ($order->cancellationRequest && in_array($order->cancellationRequest->status, ['requested', 'approved'], true))
                                            <span class="mt-1 inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-800">{{ $order->cancellationRequest->status === 'requested' ? 'Cancellation review' : 'Refund pending' }}</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-xs font-medium text-gray-600">{{ str($order->payment_status ?? 'unpaid')->title() }}</td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('orders.show', $order) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition hover:border-primary-200 hover:bg-primary-50 hover:text-primary-700">
                                            View details <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-14 text-center">
                                        <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-400"><i data-lucide="shopping-bag" class="h-5 w-5"></i></span>
                                        <p class="mt-3 font-semibold text-gray-900">No orders yet</p>
                                        <p class="mt-1 text-sm text-gray-500">New customer orders will appear here.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <script type="application/json" id="dashboard-chart-data">@json($chart_data)</script>
    @push('scripts')
        @vite('resources/js/dashboard.js')
    @endpush
</x-app-layout>
