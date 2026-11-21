<x-storefront-layout>
    <div class="container">
    <x-ui.page-header eyebrow="Akun Saya" title="Pesanan Saya" description="Pantau status dan riwayat pesanan Anda.">
        <x-slot:actions><x-ui.link-button :href="route('shop')" variant="secondary"><i data-lucide="shopping-bag" class="h-4 w-4"></i>Lanjut Belanja</x-ui.link-button></x-slot:actions>
    </x-ui.page-header>

    @if (session('success'))
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
            role="status"><i data-lucide="check-circle-2"
                class="mt-0.5 h-4 w-4 shrink-0"></i><span>{{ session('success') }}</span></div>
    @endif

    @if ($orders->isEmpty())
        <section
            class="flex min-h-[360px] flex-col items-center justify-center rounded-2xl border border-dashed border-gray-300 bg-gray-50/70 px-6 py-12 text-center">
            <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-50 text-primary-600"><i
                    data-lucide="package-search" class="h-8 w-8"></i></span>
            <h2 class="mt-5 font-semibold text-gray-900">Belum ada pesanan</h2>
            <p class="mt-2 max-w-sm text-sm leading-6 text-gray-500">Pesanan yang kamu buat akan muncul di halaman ini.
                Yuk, temukan produk yang kamu butuhkan.</p>
            <a href="{{ route('shop') }}"
                class="mt-5 inline-flex items-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">Mulai
                Belanja<i data-lucide="arrow-right" class="h-4 w-4"></i></a>
        </section>
    @else
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <div>
                    <h2 class="font-semibold text-gray-900">Riwayat Pesanan</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ $orders->total() }} pesanan tercatat</p>
                </div><span class="rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700">Akun
                    Saya</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th scope="col" class="px-5 py-3.5 font-semibold">No. Pesanan</th>
                            <th scope="col" class="px-5 py-3.5 font-semibold">Tanggal</th>
                            <th scope="col" class="px-5 py-3.5 font-semibold">Item</th>
                            <th scope="col" class="px-5 py-3.5 font-semibold">Total</th>
                            <th scope="col" class="px-5 py-3.5 font-semibold">Status Pesanan</th>
                            <th scope="col" class="px-5 py-3.5 font-semibold">Pembayaran</th>
                            <th scope="col" class="px-5 py-3.5 font-semibold">Pengiriman</th>
                            <th scope="col" class="px-5 py-3.5 text-right font-semibold">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($orders as $order)
                            @php
                                $status = strtolower($order->status);
                                $statusClass = match ($status) {
                                    'success', 'completed' => 'bg-green-50 text-green-700 ring-green-600/20',
                                    'processing', 'shipped' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                    'delivered' => 'bg-cyan-50 text-cyan-700 ring-cyan-600/20',
                                    'failed', 'cancelled' => 'bg-red-50 text-red-700 ring-red-600/20',
                                    'refund', 'refunded' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
                                    default => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                };

                                $paymentStatus = strtolower($order->payment_status ?? 'unpaid');
                                $paymentStatusClass = match ($paymentStatus) {
                                    'paid',
                                    'success',
                                    'settlement',
                                    'capture'
                                        => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                    'pending', 'waiting', 'unpaid' => 'bg-gray-50 text-gray-700 ring-gray-600/20',
                                    'failed',
                                    'expired',
                                    'deny',
                                    'cancelled'
                                        => 'bg-red-50 text-red-700 ring-red-600/20',
                                    'refund', 'refunded' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
                                    default => 'bg-gray-50 text-gray-700 ring-gray-600/20',
                                };
                            @endphp
                            <tr class="transition hover:bg-gray-50/80">
                                <td class="px-5 py-4 font-semibold text-gray-900">{{ $order->order_number }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-gray-600">
                                    {{ $order->created_at->format('d M Y, H:i') }}
                                </td>
                                <td class="px-5 py-4 text-gray-600">{{ $order->order_items_count }}</td>
                                <td class="whitespace-nowrap px-5 py-4 font-semibold text-gray-900">
                                    Rp {{ number_format($order->total, 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="{{ $statusClass }} inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset">
                                        {{ str($order->status)->replace('_', ' ')->title() }}
                                    </span>
                                    @if ($order->cancellationRequest && in_array($order->cancellationRequest->status, ['requested', 'approved'], true))
                                        <span class="mt-1 inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-800">{{ $order->cancellationRequest->status === 'requested' ? 'Pembatalan ditinjau' : 'Menunggu refund' }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="{{ $paymentStatusClass }} inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset">
                                        {{ ucfirst($paymentStatus) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    @if (strtolower((string) $order->biteship_status) === 'cancelled')
                                        <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 ring-1 ring-inset ring-red-600/20">Dibatalkan</span>
                                        <p class="mt-1 text-[11px] text-gray-500">Pengiriman tidak aktif</p>
                                        @if ($order->tracking_number || $order->biteship_waybill_id)
                                            <p class="mt-1 font-mono text-[11px] text-gray-500">Resi sebelumnya: {{ $order->tracking_number ?: $order->biteship_waybill_id }}</p>
                                        @endif
                                    @elseif (filled($order->biteship_status) || filled($order->tracking_number) || filled($order->biteship_waybill_id))
                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-600/20">{{ filled($order->biteship_status) ? str($order->biteship_status)->replace('_', ' ')->title() : 'Resi tersedia' }}</span>
                                        @if ($order->tracking_number || $order->biteship_waybill_id)
                                            <p class="mt-1 font-mono text-[11px] text-gray-500">{{ $order->tracking_number ?: $order->biteship_waybill_id }}</p>
                                        @endif
                                    @else
                                        <span class="text-xs text-gray-400">Belum tersedia</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right"><a href="{{ route('orders.customer.show', $order) }}"
                                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold text-primary-600 transition hover:bg-primary-50 hover:text-primary-700">Lihat
                                        detail<i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        @if ($orders->hasPages())
            <div class="mt-5">{{ $orders->links() }}</div>
        @endif
    @endif
    </div>
</x-storefront-layout>
