{{-- Dokumentasi: Daftar pesanan dan indikator follow-up untuk operasional admin. --}}
<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-gray-900">Orders</h2>
            <p class="mt-1 text-sm text-gray-500">Monitor order activity and prioritize operational follow-ups.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="flex w-full flex-col gap-5 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <x-alert class="border border-green-200 bg-green-50 text-green-800">{{ session('success') }}</x-alert>
            @endif

            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('orders.index') }}"
                    class="hover:border-primary-200 rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-500">Total orders</p>
                        <span class="rounded-lg bg-gray-100 p-2 text-gray-600"><i data-lucide="shopping-bag"
                                class="h-4 w-4"></i></span>
                    </div>
                    <p class="mt-3 text-3xl font-bold tracking-tight text-gray-900">
                        {{ number_format($metrics['total']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">All recorded orders</p>
                </a>
                <a href="{{ route('orders.index', ['follow_up' => 'payment']) }}"
                    class="rounded-xl border border-amber-200 bg-amber-50/60 p-5 shadow-sm transition hover:border-amber-300 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-amber-800">Awaiting payment</p>
                        <span class="rounded-lg bg-amber-100 p-2 text-amber-700"><i data-lucide="wallet"
                                class="h-4 w-4"></i></span>
                    </div>
                    <p class="mt-3 text-3xl font-bold tracking-tight text-amber-900">
                        {{ number_format($metrics['awaiting_payment']) }}</p>
                    <p class="mt-1 text-xs text-amber-700">Pending orders not paid yet</p>
                </a>
                <a href="{{ route('orders.index', ['follow_up' => 'fulfillment']) }}"
                    class="rounded-xl border border-blue-200 bg-blue-50/60 p-5 shadow-sm transition hover:border-blue-300 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-blue-800">Ready to process</p>
                        <span class="rounded-lg bg-blue-100 p-2 text-blue-700"><i data-lucide="package-check"
                                class="h-4 w-4"></i></span>
                    </div>
                    <p class="mt-3 text-3xl font-bold tracking-tight text-blue-900">
                        {{ number_format($metrics['ready_to_process']) }}</p>
                    <p class="mt-1 text-xs text-blue-700">Paid and waiting for processing</p>
                </a>
                <a href="{{ route('orders.index', ['status' => 'shipped']) }}"
                    class="rounded-xl border border-sky-200 bg-sky-50/60 p-5 shadow-sm transition hover:border-sky-300 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-sky-800">In delivery</p>
                        <span class="rounded-lg bg-sky-100 p-2 text-sky-700"><i data-lucide="truck"
                                class="h-4 w-4"></i></span>
                    </div>
                    <p class="mt-3 text-3xl font-bold tracking-tight text-sky-900">
                        {{ number_format($metrics['in_delivery']) }}</p>
                    <p class="mt-1 text-xs text-sky-700">Orders currently shipped</p>
                </a>
                <a href="{{ route('orders.index', ['follow_up' => 'delivery_today']) }}"
                    class="rounded-xl border border-amber-200 bg-amber-50/60 p-5 shadow-sm transition hover:border-amber-300 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-amber-800">Estimated today</p>
                        <span class="rounded-lg bg-amber-100 p-2 text-amber-700"><i data-lucide="calendar-clock"
                                class="h-4 w-4"></i></span>
                    </div>
                    <p class="mt-3 text-3xl font-bold tracking-tight text-amber-900">
                        {{ number_format($metrics['delivery_due_today']) }}</p>
                    <p class="mt-1 text-xs text-amber-700">Awaiting delivery confirmation</p>
                </a>
                <a href="{{ route('orders.index', ['follow_up' => 'delivery_overdue']) }}"
                    class="rounded-xl border border-rose-200 bg-rose-50/60 p-5 shadow-sm transition hover:border-rose-300 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-rose-800">Delivery overdue</p>
                        <span class="rounded-lg bg-rose-100 p-2 text-rose-700"><i data-lucide="alarm-clock"
                                class="h-4 w-4"></i></span>
                    </div>
                    <p class="mt-3 text-3xl font-bold tracking-tight text-rose-900">
                        {{ number_format($metrics['delivery_overdue']) }}</p>
                    <p class="mt-1 text-xs text-rose-700">Past estimated delivery date</p>
                </a>
                <a href="{{ route('orders.index', ['follow_up' => 'cancellation']) }}"
                    class="rounded-xl border border-orange-200 bg-orange-50/60 p-5 shadow-sm transition hover:border-orange-300 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-orange-800">Cancellation review</p>
                        <span class="rounded-lg bg-orange-100 p-2 text-orange-700"><i
                                data-lucide="message-circle-warning" class="h-4 w-4"></i></span>
                    </div>
                    <p class="mt-3 text-3xl font-bold tracking-tight text-orange-900">
                        {{ number_format($metrics['cancellation']) }}</p>
                    <p class="mt-1 text-xs text-orange-700">Requests needing a decision</p>
                </a>
                <a href="{{ route('orders.index', ['follow_up' => 'return']) }}"
                    class="rounded-xl border border-violet-200 bg-violet-50/60 p-5 shadow-sm transition hover:border-violet-300 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-violet-800">Returns / refunds</p>
                        <span class="rounded-lg bg-violet-100 p-2 text-violet-700"><i data-lucide="package-search"
                                class="h-4 w-4"></i></span>
                    </div>
                    <p class="mt-3 text-3xl font-bold tracking-tight text-violet-900">
                        {{ number_format($metrics['returns']) }}</p>
                    <p class="mt-1 text-xs text-violet-700">Return or resolution follow-up</p>
                </a>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h3 class="font-semibold text-gray-900">Filter orders</h3>
                    <p class="mt-0.5 text-sm text-gray-500">Search by order number or customer, then narrow the list by
                        status or date.</p>
                </div>
                <form method="GET" action="{{ route('orders.index') }}"
                    class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2 xl:grid-cols-6">
                    <div class="sm:col-span-2 xl:col-span-2">
                        <label for="order-search"
                            class="mb-1.5 block text-xs font-semibold text-gray-600">Search</label>
                        <div class="relative">
                            <i data-lucide="search"
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                            <input id="order-search" type="search" name="search" value="{{ request('search') }}"
                                placeholder="Order ID, customer, email..."
                                class="focus:border-primary-500 focus:ring-primary-500 w-full rounded-lg border-gray-300 pl-9 text-sm shadow-sm">
                        </div>
                    </div>
                    <div>
                        <label for="order-status" class="mb-1.5 block text-xs font-semibold text-gray-600">Order
                            status</label>
                        <select id="order-status" name="status"
                            class="focus:border-primary-500 focus:ring-primary-500 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            <option value="">All statuses</option>
                            @foreach (['pending' => 'Pending', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'success' => 'Success'] as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="payment-status" class="mb-1.5 block text-xs font-semibold text-gray-600">Payment
                            status</label>
                        <select id="payment-status" name="payment_status"
                            class="focus:border-primary-500 focus:ring-primary-500 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            <option value="">All payments</option>
                            @foreach (['unpaid' => 'Unpaid', 'pending' => 'Pending', 'paid' => 'Paid', 'failed' => 'Failed', 'expired' => 'Expired', 'refunded' => 'Refunded', 'partially_refunded' => 'Partially refunded'] as $value => $label)
                                <option value="{{ $value }}" @selected(request('payment_status') === $value)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="follow-up" class="mb-1.5 block text-xs font-semibold text-gray-600">Needs
                            follow-up</label>
                        <select id="follow-up" name="follow_up"
                            class="focus:border-primary-500 focus:ring-primary-500 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            <option value="">Any order</option>
                            <option value="payment" @selected(request('follow_up') === 'payment')>Awaiting payment</option>
                            <option value="fulfillment" @selected(request('follow_up') === 'fulfillment')>Ready to process</option>
                            <option value="delivery" @selected(request('follow_up') === 'delivery')>Delivery follow-up</option>
                            <option value="cancellation" @selected(request('follow_up') === 'cancellation')>Cancellation review</option>
                            <option value="return" @selected(request('follow_up') === 'return')>Returns / refunds</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3 sm:col-span-2 xl:col-span-2">
                        <div>
                            <label for="date-from" class="mb-1.5 block text-xs font-semibold text-gray-600">From
                                date</label>
                            <input id="date-from" type="date" name="date_from"
                                value="{{ request('date_from') }}"
                                class="focus:border-primary-500 focus:ring-primary-500 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                        </div>
                        <div>
                            <label for="date-to" class="mb-1.5 block text-xs font-semibold text-gray-600">To
                                date</label>
                            <input id="date-to" type="date" name="date_to" value="{{ request('date_to') }}"
                                class="focus:border-primary-500 focus:ring-primary-500 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                        </div>
                    </div>
                    <div class="flex items-end gap-2 sm:col-span-2 xl:col-span-6">
                        <button type="submit"
                            class="bg-primary-600 hover:bg-primary-700 inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition">
                            <i data-lucide="filter" class="h-4 w-4"></i> Apply filters
                        </button>
                        <a href="{{ route('orders.index') }}"
                            class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                            <i data-lucide="rotate-ccw" class="h-4 w-4"></i> Reset
                        </a>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div
                    class="flex flex-col gap-1 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-900">All orders</h3>
                        <p class="mt-0.5 text-sm text-gray-500">Showing
                            {{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }} of
                            {{ number_format($orders->total()) }} matching orders</p>
                    </div>
                    @if (request()->hasAny(['search', 'status', 'payment_status', 'follow_up', 'date_from', 'date_to']))
                        <span
                            class="bg-primary-50 text-primary-700 inline-flex w-fit items-center rounded-full px-3 py-1 text-xs font-semibold">Filters
                            active</span>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1080px] divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-5 py-3.5 font-semibold">Order ID</th>
                                <th class="px-5 py-3.5 font-semibold">Customer</th>
                                <th class="px-5 py-3.5 font-semibold">Date</th>
                                <th class="px-5 py-3.5 font-semibold">Items</th>
                                <th class="px-5 py-3.5 font-semibold">Total</th>
                                <th class="px-5 py-3.5 font-semibold">Order Status</th>
                                <th class="px-5 py-3.5 font-semibold">Payment</th>
                                <th class="px-5 py-3.5 font-semibold">Estimated delivery</th>
                                <th class="px-5 py-3.5 font-semibold">Tracking Number</th>
                                <th class="px-5 py-3.5 text-right font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($orders as $order)
                                <tr class="transition hover:bg-gray-50/80">
                                    <td class="px-5 py-4 font-semibold text-gray-900">{{ $order->order_number }}</td>
                                    <td class="px-5 py-4">
                                        <p class="font-medium text-gray-900">{{ $order->user_name }}</p>
                                        <p class="mt-0.5 text-xs text-gray-500">{{ $order->user_email }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-gray-600">
                                        {{ $order->created_at->format('d M Y, H:i') }}</td>
                                    <td class="px-5 py-4 text-gray-600">{{ $order->order_items_count }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 font-semibold text-gray-900">Rp
                                        {{ number_format($order->total, 0, ',', '.') }}</td>
                                    <td class="px-5 py-4">
                                        <span
                                            class="{{ in_array($order->status, ['completed', 'success'], true) ? 'bg-green-50 text-green-700' : (in_array($order->status, ['shipped', 'processing'], true) ? 'bg-blue-50 text-blue-700' : ($order->status === 'delivered' ? 'bg-cyan-50 text-cyan-700' : ($order->status === 'cancelled' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700'))) }} inline-flex rounded-full px-2.5 py-1 text-xs font-semibold">{{ str($order->status)->replace('_', ' ')->title() }}</span>
                                        @if ($order->cancellationRequest && in_array($order->cancellationRequest->status, ['requested', 'approved'], true))
                                            <span
                                                class="mt-1 inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-800">{{ $order->cancellationRequest->status === 'requested' ? 'Cancellation review' : 'Refund pending' }}</span>
                                        @endif
                                        @if ($order->returnRequest && in_array($order->returnRequest->status, ['requested', 'item_received'], true))
                                            <span
                                                class="mt-1 inline-flex rounded-full bg-violet-50 px-2.5 py-1 text-[11px] font-semibold text-violet-800">Return
                                                follow-up</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-xs font-medium text-gray-600">
                                        {{ str($order->payment_status ?? 'unpaid')->replace('_', ' ')->title() }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-xs">
                                        @if ($order->estimated_delivery_at && $order->status === 'shipped')
                                            <span
                                                class="{{ $order->estimated_delivery_at->lt(today()) ? 'text-rose-700' : ($order->estimated_delivery_at->isToday() ? 'text-amber-700' : 'text-gray-700') }} font-semibold">{{ $order->estimated_delivery_at->format('d M Y') }}</span>
                                            @if ($order->estimated_delivery_at->lt(today()))
                                                <span
                                                    class="mt-1 block text-[11px] font-semibold text-rose-600">Overdue
                                                    · {{ $order->delivery_follow_up_reminder_count }}
                                                    reminder(s)</span>
                                            @elseif ($order->estimated_delivery_at->isToday())
                                                <span class="mt-1 block text-[11px] font-semibold text-amber-600">Due
                                                    today</span>
                                            @endif
                                        @elseif ($order->status === 'shipped')
                                            <span class="text-gray-400">No ETA</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-xs">
                                        @if (strtolower((string) $order->biteship_status) === 'cancelled')
                                            <span class="inline-flex rounded-full bg-red-50 px-2 py-1 text-[11px] font-semibold text-red-700">Shipment dibatalkan</span>
                                            @if ($order->tracking_number || $order->biteship_waybill_id)
                                                <span class="mt-1 block font-mono text-[11px] text-gray-500">Resi lama: {{ $order->tracking_number ?: $order->biteship_waybill_id }}</span>
                                            @endif
                                        @else
                                            <span class="font-medium text-gray-600">{{ $order->tracking_number ?: $order->biteship_waybill_id ?: '—' }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('orders.show', $order) }}"
                                            class="hover:border-primary-200 hover:bg-primary-50 hover:text-primary-700 inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition">View
                                            details<i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i></a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-5 py-16 text-center">
                                        <p class="font-semibold text-gray-900">No matching orders</p>
                                        <p class="mt-1 text-sm text-gray-500">Try changing your search or filter
                                            criteria.</p>
                                        <a href="{{ route('orders.index') }}"
                                            class="text-primary-700 hover:text-primary-800 mt-3 inline-flex text-sm font-semibold">Clear
                                            all filters</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($orders->hasPages())
                    <div class="border-t border-gray-100 px-5 py-4">{{ $orders->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
