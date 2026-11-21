{{-- Dokumentasi: Detail pesanan admin: pembayaran, fulfillment, tracking, pembatalan, retur, dan refund. --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold tracking-tight text-gray-900">Detail Order {{ $order->order_number }}</h2>
                <p class="mt-1 text-sm text-gray-500">Placed {{ $order->created_at->format('d M Y, H:i') }}</p>
            </div><a href="{{ route('orders.index') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50"><i
                    data-lucide="arrow-left" class="h-4 w-4"></i>Back to orders</a>
        </div>
    </x-slot>
    @if (session('success'))
        <div class="mx-auto mt-5 max-w-7xl px-4 sm:px-6 lg:px-8"><x-alert
                class="border border-green-200 bg-green-50 text-green-800">{{ session('success') }}</x-alert></div>
    @endif
    @if ($errors->any())
        <div class="mx-auto mt-5 max-w-7xl space-y-1 px-4 text-sm text-red-600 sm:px-6 lg:px-8">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif
    <div class="flex flex-col gap-4 py-8">
        <div class="mx-auto grid w-full max-w-7xl grid-cols-1 items-start gap-5 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">

            <div class="flex flex-col gap-4 lg:col-span-2">
                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-5 py-4">
                        <h3 class="font-semibold text-gray-900">Order items</h3>
                        <p class="mt-0.5 text-sm text-gray-500">{{ $order->orderItems->count() }} line items</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[560px] divide-y divide-gray-200 text-left text-sm">
                            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-5 py-3.5 font-semibold">Product</th>
                                    <th class="px-5 py-3.5 font-semibold">Unit price</th>
                                    <th class="px-5 py-3.5 font-semibold">Qty</th>
                                    <th class="px-5 py-3.5 text-right font-semibold">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($order->orderItems as $item)
                                    <tr>
                                        <td class="px-5 py-4 font-medium text-gray-900">
                                            {{ $item->product_name }}
                                            @if ($item->product_variant_name && $item->product_variant_name !== 'Default')
                                                <span
                                                    class="mt-1 block text-xs font-normal text-gray-500">{{ $item->product_variant_name }}</span>
                                            @endif
                                            @if ($item->product_sku)
                                                <span class="mt-1 block text-xs font-normal text-gray-400">SKU:
                                                    {{ $item->product_sku }}</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-4 text-gray-600">Rp
                                            {{ number_format($item->price, 0, ',', '.') }}</td>
                                        <td class="px-5 py-4 text-gray-600">{{ $item->quantity }}</td>
                                        <td class="whitespace-nowrap px-5 py-4 text-right font-medium text-gray-900">Rp
                                            {{ number_format($item->total, 0, ',', '.') }}</td>
                                </tr>@empty<tr>
                                        <td colspan="4" class="px-5 py-10 text-center text-gray-500">No items in this
                                            order.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="space-y-2 border-t border-gray-100 bg-gray-50 px-5 py-4 text-sm">
                        <div class="flex justify-between"><span class="text-gray-500">Subtotal produk</span><span>Rp
                                {{ number_format($order->subtotal ?? $order->total, 0, ',', '.') }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Ongkir @if ($order->shipping_courier)
                                    ({{ $order->shipping_courier }} — {{ $order->shipping_service }})
                                @endif
                            </span>
                            <span>Rp {{ number_format($order->shipping_cost ?? 0, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between border-t border-gray-200 pt-3 font-semibold"><span>Total
                                order</span><span>Rp {{ number_format($order->total, 0, ',', '.') }}</span></div>
                    </div>


                </section>
                @if (filled($order->biteship_tracking_id))
                    @php
                        $trackingEvents = collect(
                            data_get($trackingData, 'tracking_history')
                                ?? data_get($trackingData, 'data.tracking_history')
                                ?? data_get($trackingData, 'tracking_page.tracking_history')
                                ?? data_get($trackingData, 'data.tracking_page.tracking_history')
                                ?? data_get($trackingData, 'history')
                                ?? data_get($trackingData, 'data.history')
                                ?? data_get($trackingData, 'events')
                                ?? data_get($trackingData, 'data.events')
                                ?? [],
                        )->filter(fn ($event) => is_array($event));
                    @endphp
                    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-2 border-b border-gray-100 pb-4">
                            <div>
                                <h3 class="font-semibold text-gray-900">Progress Tracking</h3>
                                <p class="mt-1 text-xs text-gray-500">ID: <span class="font-mono">{{ $order->biteship_tracking_id }}</span></p>
                                @if ($order->biteship_status)
                                    <p class="mt-1 text-xs font-semibold {{ strtolower((string) $order->biteship_status) === 'cancelled' ? 'text-red-700' : 'text-teal-700' }}">Status Biteship: {{ str($order->biteship_status)->replace('_', ' ')->title() }}</p>
                                @endif
                            </div>
                            @if ($order->biteship_last_synced_at)
                                <span class="text-xs text-gray-500">Sinkronisasi: {{ $order->biteship_last_synced_at->format('d M Y, H:i') }}</span>
                            @endif
                        </div>

                        @if (strtolower((string) $order->biteship_status) === 'cancelled')
                            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                                <p class="font-semibold">Pengiriman dibatalkan</p>
                                <p class="mt-1">Shipment ini sudah dibatalkan di Biteship dan tidak lagi dalam proses pengiriman. Riwayat di bawah ini hanya merupakan catatan aktivitas sebelum pembatalan.</p>
                            </div>
                        @endif

                        @if ($trackingError)
                            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                                {{ $trackingError }} Silakan muat ulang halaman untuk mencoba kembali.
                            </div>
                        @elseif ($trackingEvents->isNotEmpty())
                            <ol class="relative mt-5 space-y-6 before:absolute before:bottom-2 before:left-[11px] before:top-2 before:w-0.5 before:bg-teal-400">
                                @foreach ($trackingEvents as $event)
                                    @php
                                        $eventStatus = data_get($event, 'status') ?? data_get($event, 'title') ?? data_get($event, 'checkpoint') ?? 'Update pengiriman';
                                        $eventDescription = data_get($event, 'description') ?? data_get($event, 'message') ?? data_get($event, 'note') ?? data_get($event, 'details');
                                        $eventDate = data_get($event, 'updated_at') ?? data_get($event, 'created_at') ?? data_get($event, 'datetime') ?? data_get($event, 'date');
                                    @endphp
                                    <li class="relative flex gap-4 pl-0">
                                        <span class="relative z-10 flex h-6 w-6 shrink-0 items-center justify-center rounded-full {{ strtolower((string) $order->biteship_status) === 'cancelled' ? 'bg-red-500' : 'bg-teal-500' }} text-white ring-4 ring-white">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M3 5.5A1.5 1.5 0 0 1 4.5 4h8A1.5 1.5 0 0 1 14 5.5V7h1.2a1.5 1.5 0 0 1 1.2.6l1.3 1.7c.2.3.3.6.3.9v2.3a1.5 1.5 0 0 1-1.5 1.5h-.6a2.4 2.4 0 0 1-4.6 0H8.7a2.4 2.4 0 0 1-4.6 0h-.6A1.5 1.5 0 0 1 2 12.5v-1A1.5 1.5 0 0 1 3.5 10H4V5.5Zm11 3v2h2.5v-.2L15.3 8.5H14ZM6.4 14.5a.9.9 0 1 0 0-1.8.9.9 0 0 0 0 1.8Zm7 0a.9.9 0 1 0 0-1.8.9.9 0 0 0 0 1.8Z"/></svg>
                                        </span>
                                        <div class="min-w-0 flex-1 pb-1">
                                            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                                <strong class="text-sm font-semibold text-gray-900">{{ str($eventStatus)->replace('_', ' ')->title() }}</strong>
                                                @if ($eventDate)
                                                    <time class="text-xs text-gray-500">{{ \Illuminate\Support\Carbon::parse($eventDate)->format('d M · H:i') }}</time>
                                                @endif
                                            </div>
                                            @if (filled($eventDescription))
                                                <p class="mt-1 text-sm leading-6 text-gray-600">{{ $eventDescription }}</p>
                                            @endif
                                            @if (filled(data_get($event, 'waybill_id')))
                                                <p class="mt-1 text-xs text-gray-500">Nomor resi: {{ data_get($event, 'waybill_id') }}</p>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        @else
                            <p class="mt-4 rounded-lg bg-gray-50 px-4 py-6 text-center text-sm text-gray-500">Belum ada riwayat perjalanan paket dari Biteship.</p>
                        @endif
                    </section>
                @elseif ($order->biteship_order_id)
                    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <h3 class="font-semibold text-gray-900">Progress Tracking</h3>
                        <p class="mt-2 text-sm text-gray-500">Tracking ID belum tersedia. Riwayat pengiriman akan muncul setelah Biteship menerbitkan tracking ID.</p>
                    </section>
                @endif
                @if ($order->shipments->count() > 1)
                    <section class="mt-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <h3 class="font-semibold text-gray-900">Riwayat Pengiriman</h3>
                        <ol class="mt-3 space-y-3">
                            @foreach ($order->shipments as $shipment)
                                <li class="rounded-lg border border-gray-100 p-3">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <span class="text-sm font-semibold text-gray-900">Percobaan ke-{{ $shipment->attempt }}</span>
                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ strtolower((string) $shipment->status) === 'cancelled' ? 'bg-red-50 text-red-700' : 'bg-teal-50 text-teal-700' }}">{{ str($shipment->status ?: 'unknown')->replace('_', ' ')->title() }}</span>
                                    </div>
                                    <p class="mt-1 break-all font-mono text-xs text-gray-600">{{ $shipment->biteship_order_id }}</p>
                                    @if ($shipment->waybill_id)
                                        <p class="mt-1 text-xs text-gray-500">AWB: {{ $shipment->waybill_id }}</p>
                                    @endif
                                    @if ($shipment->cancellation_reason)
                                        <p class="mt-1 text-xs text-gray-500">Alasan: {{ $shipment->cancellation_reason }}</p>
                                    @endif
                                    <p class="mt-1 text-xs text-gray-400">{{ $shipment->created_at_biteship?->format('d M Y, H:i') ?: $shipment->created_at->format('d M Y, H:i') }}</p>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif
            </div>

            <aside class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h3 class="font-semibold text-gray-900">Customer information</h3>
                </div>
                <dl class="divide-y divide-gray-100 px-5">
                    @foreach ([
        ['Customer', $order->user_name],
        ['Email', $order->user_email],
        ['Recipient', ($order->recipient_name ?: $order->user_name) . ' · ' . ($order->user_phone ?: 'No. telepon tidak tersedia')],
        [
            'Shipping address',
            collect([$order->address, $order->city, $order->state, $order->zip, $order->country])->filter()->join(', ') ?:
            'Belum diisi',
        ],
        ['Order date', $order->created_at->format('d M Y, H:i')],
    ] as [$label, $value])
                        <div class="py-3.5">
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ $label }}
                            </dt>
                            <dd class="mt-1 break-words text-sm font-medium text-gray-900">{{ $value }}</dd>
                        </div>
                    @endforeach
                    <div class="py-3.5">
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Status pesanan</dt>
                        <dd class="mt-2 text-sm font-semibold text-gray-900">{{ ucfirst($order->status) }}</dd>
                    </div>
                    <div class="py-3.5">
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Status pembayaran</dt>
                        <dd class="mt-2 text-sm font-semibold text-gray-900">
                            {{ ucfirst($order->payment_status ?? 'unpaid') }}</dd>
                    </div>
                    @if ($order->shipped_at)
                        <div class="py-3.5">
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Tanggal dikirim</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">
                                {{ $order->shipped_at->format('d M Y, H:i') }}</dd>
                        </div>
                        <div class="py-3.5">
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Estimasi sampai</dt>
                            <dd
                                class="{{ $order->status === 'shipped' && $order->estimated_delivery_at?->lt(today()) ? 'text-rose-700' : 'text-gray-900' }} mt-1 text-sm font-semibold">
                                {{ $order->estimated_delivery_at?->format('d M Y') ?? 'Tidak tersedia dari layanan kurir' }}
                            </dd>
                            @if ($order->status === 'shipped' && $order->estimated_delivery_at?->lt(today()))
                                <p class="mt-1 text-xs text-rose-600">Lewat estimasi ·
                                    {{ $order->delivery_follow_up_reminder_count }} pengingat telah dikirim</p>
                                <p class="mt-1 text-xs text-gray-500">Periksa status dengan kurir dan hubungi customer.
                                    Jangan ubah ke Delivered sebelum ada konfirmasi.</p>
                            @elseif ($order->status === 'shipped' && $order->estimated_delivery_at?->isToday())
                                <p class="mt-1 text-xs text-amber-600">Estimasi sampai hari ini</p>
                            @endif
                        </div>
                    @endif
                </dl>
            </aside>

            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-3">
                <h3 class="font-semibold text-gray-900">Update status pesanan</h3>
                <p class="mt-1 text-sm text-gray-500">Admin mengatur proses pesanan. Completed hanya ditetapkan oleh
                    konfirmasi pelanggan atau sistem otomatis.</p>
                @if ($order->cancellationRequest && in_array($order->cancellationRequest->status, ['requested', 'approved'], true))
                    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        <p class="font-semibold">Update status pesanan dikunci</p>
                        <p class="mt-1">Ada pengajuan pembatalan yang
                            {{ $order->cancellationRequest->status === 'requested' ? 'menunggu keputusan admin' : 'telah disetujui dan menunggu penyelesaian refund' }}.
                            Pesanan tidak dapat diproses atau dilanjutkan ke tahap berikutnya sampai pengajuan ditolak
                            atau pembatalan diselesaikan.</p>
                    </div>
                @elseif ($order->status === 'pending')
                    <form action="{{ route('orders.status.update', $order) }}" method="POST" class="mt-4 space-y-3">
                        @csrf @method('PATCH')
                        <div>
                            <span class="mb-1.5 block text-sm font-medium text-gray-700">Status berikutnya</span>
                            @if ($order->payment_status === 'paid')
                                <input type="hidden" name="status" value="processing">
                                <div class="rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm text-gray-800">
                                    Processing — buat pengiriman Biteship
                                </div>
                            @else
                                <label for="order-status" class="sr-only">Status berikutnya</label>
                                <select id="order-status" name="status" required
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm">
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500">Setelah disimpan, RevStore akan membuat shipment di Biteship
                            menggunakan kurir dan layanan yang dipilih pelanggan.</p>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="submit"
                                class="bg-primary-600 hover:bg-primary-700 inline-flex justify-center rounded-lg px-5 py-2.5 text-sm font-semibold text-white">Simpan
                                status & buat pengiriman</button>
                            <button type="button" disabled title="Tersedia setelah pengiriman berhasil dibuat"
                                class="inline-flex cursor-not-allowed items-center justify-center rounded-lg border border-gray-300 bg-gray-100 px-5 py-2.5 text-sm font-semibold text-gray-400">
                                Print Resi
                            </button>
                        </div>
                    </form>
                @elseif ($order->status === 'processing')
                    @if ($order->biteship_order_id)
                        @if (strtolower((string) $order->biteship_status) === 'cancelled')
                            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-900">
                                <p class="font-semibold">Shipment Biteship dibatalkan</p>
                                <p class="mt-1">Pengiriman tidak lagi aktif. Status pesanan dan pembayaran tidak berubah otomatis akibat pembatalan shipment ini.</p>
                                <p class="mt-2">ID Biteship: <span class="break-all font-mono">{{ $order->biteship_order_id }}</span></p>
                                @if ($order->biteship_waybill_id)
                                    <p class="mt-1">Resi sebelumnya: <strong class="font-mono">{{ $order->biteship_waybill_id }}</strong></p>
                                @endif
                                @if ($order->biteship_last_synced_at)
                                    <p class="mt-1 text-xs">Sinkronisasi terakhir: {{ $order->biteship_last_synced_at->format('d M Y, H:i') }}</p>
                                @endif
                                <p class="mt-3 text-xs">Jika customer masih ingin menerima barang, admin dapat membuat shipment pengganti. Jika pesanan tidak dapat dipenuhi, gunakan alur pengajuan pembatalan dan refund.</p>
                                <form action="{{ route('orders.replacement-shipment-rates', $order) }}" method="POST" class="mt-4">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-700">
                                        <i data-lucide="calculator" class="h-4 w-4"></i> Hitung Tarif Pengiriman Baru
                                    </button>
                                </form>
                                @if (session()->has('replacement_shipment_rates'))
                                    @php($replacementRates = session('replacement_shipment_rates'))
                                    @if (count($replacementRates))
                                        <form action="{{ route('orders.retry-shipment', $order) }}" method="POST" class="mt-3 rounded-lg border border-teal-200 bg-white p-3" onsubmit="return confirm('Buat shipment baru menggunakan kurir dan layanan yang dipilih?')">
                                            @csrf
                                            <label for="replacement-shipping-service" class="mb-1 block text-xs font-semibold">Pilih kurir dan layanan</label>
                                            <select id="replacement-shipping-service" name="service_selection" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" onchange="const selected = JSON.parse(this.value); this.form.courier_code.value = selected.courier_code; this.form.service_code.value = selected.service_code;">
                                                @foreach ($replacementRates as $rate)
                                                    <option value="{{ json_encode(['courier_code' => $rate['courier_code'], 'service_code' => $rate['service_code']]) }}">{{ $rate['courier_name'] }} · {{ $rate['service_name'] }} · Rp {{ number_format($rate['price'], 0, ',', '.') }}{{ $rate['duration'] ? ' · '.$rate['duration'] : '' }}</option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="courier_code" value="{{ $replacementRates[0]['courier_code'] }}">
                                            <input type="hidden" name="service_code" value="{{ $replacementRates[0]['service_code'] }}">
                                            <button type="submit" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white">Buat Pengiriman Baru</button>
                                        </form>
                                    @else
                                        <p class="mt-2 text-xs text-amber-700">Tidak ada layanan kurir yang tersedia untuk alamat ini.</p>
                                    @endif
                                @endif
                                @if (in_array($order->status, ['pending', 'processing'], true) && ! $order->cancellationRequest && ! $order->returnRequest)
                                    <form action="{{ route('orders.request-cancellation', $order) }}" method="POST" class="mt-4 border-t border-red-200 pt-4" onsubmit="return confirm('Mulai proses pembatalan pesanan? Jika sudah dibayar, pengajuan harus dilanjutkan melalui proses refund.')">
                                        @csrf
                                        <label for="shipment-cancellation-reason" class="mb-1 block text-xs font-semibold">Atau batalkan pesanan</label>
                                        <select id="shipment-cancellation-reason" name="reason" required class="w-full max-w-sm rounded-lg border border-red-200 bg-white px-3 py-2 text-sm">
                                            <option value="other">Pengiriman tidak dapat dilanjutkan</option>
                                            <option value="wrong_address">Alamat perlu diperbaiki</option>
                                            <option value="changed_mind">Customer tidak ingin melanjutkan</option>
                                            <option value="other">Alasan lainnya</option>
                                        </select>
                                        <textarea name="description" rows="2" maxlength="2000" placeholder="Catatan pembatalan (opsional)" class="mt-2 w-full rounded-lg border border-red-200 px-3 py-2 text-sm"></textarea>
                                        <button type="submit" class="mt-2 inline-flex items-center gap-2 rounded-lg border border-red-300 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-100">Batalkan Pesanan</button>
                                        @if ($order->payment_status === 'paid')
                                            <p class="mt-2 text-xs text-red-700">Karena pembayaran sudah lunas, tindakan ini membuat pengajuan pembatalan. Refund tetap harus diproses dan dikonfirmasi melalui alur Midtrans.</p>
                                        @else
                                            <p class="mt-2 text-xs text-red-700">Pesanan belum dibayar. Pesanan akan dibatalkan dan stok dikembalikan.</p>
                                        @endif
                                    </form>
                                @endif
                            </div>
                        @else
                        <div
                            class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                            <p class="font-semibold">Shipment Biteship berhasil dibuat</p>
                            <p class="mt-1">ID Biteship: <span
                                    class="font-mono">{{ $order->biteship_order_id }}</span></p>
                            <p>Status Biteship:
                                <strong>{{ str($order->biteship_status ?? 'confirmed')->replace('_', ' ')->title() }}</strong>
                            </p>
                            @if ($order->biteship_waybill_id)
                                <p>AWB: <strong class="font-mono">{{ $order->biteship_waybill_id }}</strong></p>
                            @endif
                            @if ($order->biteship_last_synced_at)
                                <p class="mt-1 text-xs">Sinkronisasi terakhir:
                                    {{ $order->biteship_last_synced_at->format('d M Y, H:i') }}</p>
                            @endif
                            <a href="{{ route('orders.shipping-label', $order) }}" target="_blank" rel="noopener noreferrer"
                                class="mt-3 inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-700">
                                <i data-lucide="printer" class="h-4 w-4"></i>
                                Print Resi
                            </a>
                        </div>
                        @endif
                    @else
                        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                            <p class="font-semibold">Pengiriman Biteship belum dibuat</p>
                            <p class="mt-1">Jalankan audit untuk mengetahui penyebabnya. Hasil pemeriksaan dan setiap
                                percobaan akan tersimpan seperti Payment Audit.</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <form action="{{ route('biteship-reconciliation.check', $order) }}" method="POST">
                                    @csrf<button type="submit"
                                        class="rounded-lg border border-amber-700 px-4 py-2 text-sm font-semibold text-amber-800">Periksa
                                        kesiapan</button></form>
                                <form action="{{ route('biteship-reconciliation.retry', $order) }}" method="POST">
                                    @csrf<button type="submit"
                                        class="rounded-lg bg-amber-700 px-4 py-2 text-sm font-semibold text-white">Coba
                                        buat pengiriman lagi</button></form>
                                <a href="{{ route('biteship-reconciliation.index', ['search' => $order->order_number]) }}"
                                    class="rounded-lg border border-amber-200 px-4 py-2 text-sm font-semibold text-amber-900">Buka
                                    Biteship Audit</a>
                            </div>
                        </div>
                    @endif
                    @if (strtolower((string) $order->biteship_status) !== 'cancelled')
                        <p class="mt-3 text-xs text-gray-500">Status Shipped dan Delivered akan diperbarui otomatis dari
                            webhook Biteship.</p>
                    @endif
                @endif
                @if ($order->tracking_number)
                    <p class="mt-3 text-sm text-gray-700">{{ strtolower((string) $order->biteship_status) === 'cancelled' ? 'Nomor resi sebelumnya' : 'Nomor resi' }}: <strong>{{ $order->tracking_number }}</strong>
                    </p>
                @endif
                @if ($order->delivered_at && $order->status === 'delivered')
                    <p class="mt-3 text-xs text-amber-700">Auto-complete dijadwalkan pada
                        {{ $order->auto_complete_at?->format('d M Y, H:i') ?? '—' }}.</p>
                @endif
                @if ($order->payment_status !== 'paid' && $order->payment_status !== 'refunded')
                    <p class="mt-3 text-xs text-amber-700">Pesanan belum dapat diproses sebelum pembayaran
                        terverifikasi.
                    </p>
                @endif
                @if ($order->status === 'cancelled')
                    <p class="mt-3 text-xs text-red-700">Pesanan dibatalkan. Tidak dapat diproses lebih lanjut.</p>
                @endif
            </section>

        </div>

        @if ($order->cancellationRequest)
            <section class="mx-auto mb-8 w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-xl border border-amber-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="font-semibold text-gray-900">Pengajuan pembatalan</h3>
                        <span
                            class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800">{{ str($order->cancellationRequest->status)->replace('_', ' ')->title() }}</span>
                    </div>
                    <p class="mt-3 text-sm text-gray-700">Alasan:
                        {{ str($order->cancellationRequest->reason)->replace('_', ' ')->title() }}</p>
                    @if ($order->cancellationRequest->description)
                        <p class="mt-1 whitespace-pre-line text-sm text-gray-600">
                            {{ $order->cancellationRequest->description }}</p>
                    @endif
                    @if ($order->cancellationRequest->admin_note)
                        <p class="mt-2 text-sm text-gray-600">Catatan admin:
                            {{ $order->cancellationRequest->admin_note }}</p>
                    @endif
                    @if ($order->cancellationRequest->status === 'requested')
                        <form action="{{ route('order-cancellations.update', $order->cancellationRequest) }}"
                            method="POST" class="mt-4 grid gap-3 sm:grid-cols-2">@csrf @method('PATCH')
                            <input type="hidden" name="status" value="approved">
                            <div><label for="cancellation-admin-note"
                                    class="mb-1 block text-sm font-medium text-gray-700">Catatan admin
                                    (opsional)</label>
                                <textarea id="cancellation-admin-note" name="admin_note" rows="2"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm">{{ old('admin_note') }}</textarea>
                            </div>
                            <div class="flex flex-wrap items-end gap-2"><button type="submit"
                                    class="bg-primary-600 rounded-lg px-5 py-2.5 text-sm font-semibold text-white">Setujui
                                    pembatalan</button></div>
                        </form>
                        <form action="{{ route('order-cancellations.update', $order->cancellationRequest) }}"
                            method="POST" class="mt-3 grid gap-2 sm:grid-cols-2">@csrf @method('PATCH')<input
                                type="hidden" name="status" value="rejected">
                            <div><label for="cancellation-rejection-note"
                                    class="mb-1 block text-sm font-medium text-gray-700">Alasan penolakan</label>
                                <textarea id="cancellation-rejection-note" name="admin_note" required rows="2"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm">{{ old('admin_note') }}</textarea>
                            </div>
                            <div class="flex items-end"><button type="submit"
                                    class="rounded-lg border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-600">Tolak
                                    pengajuan</button></div>
                        </form>
                    @elseif ($order->cancellationRequest->status === 'approved' && $order->payment_status === 'paid')
                        @if ($order->cancellationRequest->refund_request_status === 'submitted')
                            <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                                <p class="font-semibold">Permintaan refund sudah dikirim ke Midtrans</p>
                                <p class="mt-1">Nominal: Rp
                                    {{ number_format($order->cancellationRequest->refund_amount ?? $order->total, 0, ',', '.') }}
                                    · Referensi: {{ $order->cancellationRequest->refund_reference }}</p>
                                <p class="mt-1">Menunggu webhook konfirmasi dari Midtrans. Status lokal tetap Paid
                                    sampai notifikasi refund diterima.</p>
                            </div>
                        @else
                            <form action="{{ route('order-cancellations.update', $order->cancellationRequest) }}"
                                method="POST" enctype="multipart/form-data" class="mt-4 grid gap-3 sm:grid-cols-2">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="completed">
                                <div class="sm:col-span-2"><label for="cancellation-refund-method"
                                        class="mb-1 block text-sm font-medium text-gray-700">Metode
                                        refund</label><select id="cancellation-refund-method" name="refund_method"
                                        required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
                                        <option value="midtrans" @selected(old('refund_method', 'midtrans') === 'midtrans')
                                            @disabled(!$order->transaction_id || data_get($order->payment_payload, 'transaction_status') !== 'settlement')>Otomatis via Midtrans @if (!$order->transaction_id || data_get($order->payment_payload, 'transaction_status') !== 'settlement')
                                                (tidak tersedia untuk transaksi ini)
                                            @endif
                                        </option>
                                        <option value="manual" @selected(old('refund_method') === 'manual')>Transfer manual</option>
                                    </select>
                                    @error('refund_method')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div><label for="cancellation-admin-note"
                                        class="mb-1 block text-sm font-medium text-gray-700">Catatan / referensi refund
                                        manual</label>
                                    <textarea id="cancellation-admin-note" name="admin_note" rows="2"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm">{{ old('admin_note') }}</textarea>
                                    @error('admin_note')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div><x-file-upload id="refund-proof-upload-{{ $order->cancellationRequest->id }}"
                                        label="Bukti transfer (khusus manual)" name="refund_proof"
                                        accept="image/jpeg,image/png,image/webp,application/pdf"
                                        help-text="Wajib untuk transfer manual · JPG, PNG, WEBP, or PDF"
                                        :max-size="5" mode="deferred" />
                                    @error('refund_proof')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="sm:col-span-2"><button type="submit"
                                        class="bg-primary-600 rounded-lg px-5 py-2.5 text-sm font-semibold text-white">Proses
                                        refund</button></div>
                            </form>
                        @endif
                    @elseif ($order->cancellationRequest->status === 'approved' && $order->payment_status === 'refunded')
                        <div class="mt-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-900">
                            <p class="font-semibold">Refund sudah dikonfirmasi Midtrans</p>
                            <p class="mt-1">Referensi:
                                {{ $order->cancellationRequest->refund_reference ?: 'Tercatat melalui webhook Midtrans' }}
                            </p>
                            <p class="mt-1">Selesaikan pembatalan untuk mengembalikan stok.</p>
                        </div>
                        <form action="{{ route('order-cancellations.update', $order->cancellationRequest) }}"
                            method="POST" class="mt-3">@csrf @method('PATCH')<input type="hidden"
                                name="status" value="completed"><input type="hidden" name="refund_method"
                                value="midtrans"><button type="submit"
                                class="bg-primary-600 rounded-lg px-5 py-2.5 text-sm font-semibold text-white">Konfirmasi
                                penyelesaian</button></form>
                    @elseif ($order->cancellationRequest->status === 'approved')
                        <form action="{{ route('order-cancellations.update', $order->cancellationRequest) }}"
                            method="POST" class="mt-4">@csrf @method('PATCH')<input type="hidden"
                                name="status" value="completed"><button type="submit"
                                class="bg-primary-600 rounded-lg px-5 py-2.5 text-sm font-semibold text-white">Selesaikan
                                pembatalan tanpa refund</button></form>
                    @endif
                    @if ($order->cancellationRequest->status === 'approved')
                        <p class="mt-3 text-xs text-amber-700">Pesanan ditahan dan tidak dapat dikirim. Refund otomatis
                            hanya dikirim untuk transaksi Midtrans berstatus settlement; status lokal diperbarui setelah
                            webhook refund diterima.</p>
                    @endif
                    @if ($order->cancellationRequest->refund_proof_path)
                        <div class="mt-4 rounded-lg border border-purple-100 bg-purple-50 p-3 text-sm">
                            <p class="font-semibold text-purple-900">Bukti refund tersimpan</p><a
                                href="{{ route('order-cancellations.proof', $order->cancellationRequest) }}"
                                target="_blank" rel="noopener noreferrer"
                                class="mt-1 inline-flex font-medium text-purple-700 underline">Lihat bukti transaksi ·
                                {{ $order->cancellationRequest->refund_proof_original_name }}</a>
                        </div>
                    @endif
                </div>
            </section>
        @endif

        @if ($order->returnRequest)
            <section class="mx-auto mb-8 w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="font-semibold text-gray-900">Pengajuan pengembalian</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ str($order->returnRequest->reason)->replace('_', ' ')->title() }} ·
                        {{ ucfirst($order->returnRequest->status) }}</p>
                    <p class="mt-3 whitespace-pre-line text-sm text-gray-700">{{ $order->returnRequest->description }}
                    </p>
                    @if ($order->returnRequest->evidence_paths)
                        <div class="mt-3 flex flex-wrap gap-3">
                            @foreach ($order->returnRequest->evidence_paths as $path)
                                @php($evidenceUrl = route('order-returns.evidence', [$order->returnRequest, $loop->index]))
                                @if (in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['mp4', 'mov', 'webm'], true))
                                    <div class="w-full max-w-lg rounded-xl border border-gray-200 p-3">
                                        <p class="mb-2 text-sm font-medium text-gray-700">Video unboxing
                                            {{ $loop->iteration }}</p>
                                        <video controls preload="metadata" class="w-full rounded-lg bg-black">
                                            <source src="{{ $evidenceUrl }}">
                                            Browser tidak mendukung pemutaran video. <a href="{{ $evidenceUrl }}"
                                                class="underline">Buka video</a>.
                                        </video>
                                    </div>
                                @else
                                    <a href="{{ $evidenceUrl }}" target="_blank" rel="noopener noreferrer"
                                        class="text-primary-700 rounded-lg border px-3 py-2 text-sm font-medium">Lihat
                                        foto {{ $loop->iteration }}</a>
                                @endif
                            @endforeach
                        </div>
                    @endif
                    @if ($order->returnRequest->admin_note)
                        <p class="mt-3 text-sm text-gray-600">Catatan admin: {{ $order->returnRequest->admin_note }}
                        </p>
                    @endif
                    @if ($order->returnRequest->revisions->isNotEmpty())
                        <div class="mt-4 space-y-3">
                            <h4 class="text-sm font-semibold text-gray-900">Riwayat permintaan dokumen</h4>
                            @foreach ($order->returnRequest->revisions as $revision)
                                <div class="rounded-lg border border-gray-200 p-3 text-sm">
                                    <div class="flex flex-wrap items-center justify-between gap-2"><strong>Revisi
                                            {{ $loop->iteration }}</strong><span
                                            class="rounded-full bg-gray-100 px-2 py-1 text-xs">{{ ucfirst($revision->status) }}</span>
                                    </div>
                                    <p class="mt-2 whitespace-pre-line">{{ $revision->message }}</p>
                                    <p class="mt-1 text-xs text-gray-500">Dokumen diminta:
                                        {{ collect($revision->required_documents)->map(fn($doc) => ['video_unboxing' => 'Video unboxing', 'product_photos' => 'Foto produk', 'packaging_photos' => 'Foto kemasan', 'other' => 'Dokumen lainnya'][$doc] ?? $doc)->join(', ') }}
                                    </p>
                                    @if ($revision->customer_note)
                                        <p class="mt-2">Catatan pelanggan: {{ $revision->customer_note }}</p>
                                    @endif
                                    @foreach ($revision->evidence_paths ?? [] as $path)
                                        <a class="text-primary-700 mr-2 mt-2 inline-block underline"
                                            href="{{ route('order-returns.revisions.evidence', [$order->returnRequest, $revision, $loop->index]) }}">{{ $revision->evidence_original_names[$loop->index] ?? 'Lampiran ' . $loop->iteration }}</a>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @endif
                    @if ($order->returnRequest->status === 'requested')
                        <form action="{{ route('order-returns.revisions.request', $order->returnRequest) }}"
                            method="POST" class="mt-4 space-y-3 rounded-lg border border-amber-200 bg-amber-50 p-4">
                            @csrf
                            <h4 class="font-semibold text-amber-900">Minta dokumen tambahan</h4>
                            <p class="text-xs text-amber-800">Pilih dokumen yang wajib dilengkapi pelanggan. Pengajuan
                                tidak ditolak dan dapat diperiksa kembali setelah dokumen dikirim.</p>
                            <div class="grid gap-2 text-sm sm:grid-cols-2">
                                <label class="flex items-center gap-2"><input type="checkbox"
                                        name="required_documents[]" value="video_unboxing"
                                        class="rounded border-gray-300"> Video unboxing</label>
                                <label class="flex items-center gap-2"><input type="checkbox"
                                        name="required_documents[]" value="product_photos"
                                        class="rounded border-gray-300"> Foto kondisi produk</label>
                                <label class="flex items-center gap-2"><input type="checkbox"
                                        name="required_documents[]" value="packaging_photos"
                                        class="rounded border-gray-300"> Foto kemasan</label>
                                <label class="flex items-center gap-2"><input type="checkbox"
                                        name="required_documents[]" value="other" class="rounded border-gray-300">
                                    Dokumen lainnya</label>
                            </div>
                            <textarea name="message" required minlength="10" maxlength="3000" rows="3"
                                class="w-full rounded-lg border-gray-300 text-sm"
                                placeholder="Jelaskan dokumen yang harus dilengkapi dan alasannya..."></textarea>
                            <button
                                class="rounded-lg border border-amber-300 bg-white px-4 py-2 text-sm font-semibold text-amber-900">Minta
                                dokumen tambahan</button>
                        </form>
                        <form action="{{ route('order-returns.update', $order->returnRequest) }}" method="POST"
                            class="mt-4 grid gap-3 sm:grid-cols-2">@csrf @method('PATCH')
                            <input type="hidden" name="status" value="approved">
                            <div
                                class="bg-primary-50 text-primary-900 rounded-lg p-3 text-sm font-semibold sm:col-span-2">
                                Alamat tujuan pengembalian (wajib saat menyetujui)</div>
                            <div><label class="mb-1 block text-sm font-medium">Nama penerima</label><input
                                    name="return_recipient" required class="w-full rounded-lg border-gray-300 text-sm"
                                    placeholder="Nama toko / gudang"></div>
                            <div><label class="mb-1 block text-sm font-medium">Nomor telepon</label><input
                                    name="return_phone" required class="w-full rounded-lg border-gray-300 text-sm"
                                    placeholder="08xxxxxxxxxx"></div>
                            <div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium">Alamat
                                    lengkap</label>
                                <textarea name="return_address" required rows="3" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                            </div>
                            <div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium">Instruksi
                                    pengiriman</label>
                                <textarea name="return_instructions" rows="2" class="w-full rounded-lg border-gray-300 text-sm"
                                    placeholder="Contoh: Cantumkan nomor pesanan pada paket."></textarea>
                            </div>
                            <div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium">Catatan
                                    admin</label>
                                <textarea name="admin_note" rows="2" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                            </div>
                            <div class="flex flex-wrap gap-2 sm:col-span-2"><button
                                    class="bg-primary-600 rounded-lg px-5 py-2.5 text-sm font-semibold text-white">Setujui
                                    & kirim alamat</button></div>
                        </form>
                        <form action="{{ route('order-returns.update', $order->returnRequest) }}" method="POST"
                            class="mt-3 flex flex-wrap gap-2">@csrf @method('PATCH')<input type="hidden"
                                name="status" value="rejected"><input name="admin_note" required
                                class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm"
                                placeholder="Alasan penolakan wajib diisi"><button
                                class="rounded-lg border border-red-300 px-4 py-2 text-sm font-semibold text-red-700">Tolak
                                pengajuan</button></form>
                    @elseif ($order->returnRequest->status === 'approved')
                        <div class="border-primary-100 bg-primary-50 mt-4 rounded-lg border p-4 text-sm">
                            <p class="text-primary-900 font-semibold">Menunggu pelanggan mengirim barang kembali</p>
                            <p class="mt-1">{{ $order->returnRequest->return_recipient }} ·
                                {{ $order->returnRequest->return_phone }}</p>
                            <p class="mt-1 whitespace-pre-line">{{ $order->returnRequest->return_address }}</p>
                        </div>
                    @elseif ($order->returnRequest->status === 'return_shipped')
                        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm">
                            <p class="font-semibold">Barang sedang dikirim pelanggan</p>
                            <p>{{ $order->returnRequest->return_courier }} ·
                                {{ $order->returnRequest->return_tracking_number }}</p>
                        </div>
                        <form action="{{ route('order-returns.receive', $order->returnRequest) }}" method="POST"
                            class="mt-3 space-y-2">@csrf<label class="block text-sm font-medium">Catatan pemeriksaan
                                (opsional)</label>
                            <textarea name="inspection_note" rows="2" class="w-full rounded-lg border-gray-300 text-sm"></textarea><button
                                class="bg-primary-600 rounded-lg px-5 py-2.5 text-sm font-semibold text-white">Konfirmasi
                                barang diterima</button>
                        </form>
                    @elseif ($order->returnRequest->status === 'item_received')
                        <div class="mt-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm">
                            <p class="font-semibold text-green-900">Barang diterima
                                {{ $order->returnRequest->item_received_at?->format('d M Y, H:i') }}</p>
                            <p class="mt-1">{{ $order->returnRequest->inspection_note }}</p>
                        </div>
                        @if ($order->returnRequest->resolution_status === 'pending')
                            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                                <form action="{{ route('order-returns.resolve', $order->returnRequest) }}"
                                    method="POST" enctype="multipart/form-data"
                                    class="space-y-3 rounded-lg border border-gray-200 p-4">@csrf<input type="hidden"
                                        name="resolution_type" value="refund">
                                    <h4 class="font-semibold">Refund pelanggan</h4>
                                    <div><label class="mb-1 block text-sm font-medium">Nominal refund (maks. Rp
                                            {{ number_format($order->total, 0, ',', '.') }})</label><input
                                            type="number" name="refund_amount" min="1"
                                            max="{{ $order->total }}" value="{{ $order->total }}" required
                                            class="w-full rounded-lg border-gray-300 text-sm"></div>
                                    <div><label class="mb-1 block text-sm font-medium">Metode refund</label><select
                                            name="refund_method" class="w-full rounded-lg border-gray-300 text-sm">
                                            <option value="midtrans" @disabled(!$order->transaction_id || data_get($order->payment_payload, 'transaction_status') !== 'settlement')>Otomatis via Midtrans
                                                @if (!$order->transaction_id || data_get($order->payment_payload, 'transaction_status') !== 'settlement')
                                                    (tidak tersedia untuk transaksi ini)
                                                @endif
                                            </option>
                                            <option value="manual">Transfer manual</option>
                                        </select></div>
                                    <div><label class="mb-1 block text-sm font-medium">Referensi refund
                                            manual</label><input name="refund_reference"
                                            class="w-full rounded-lg border-gray-300 text-sm"></div>
                                    <div><label class="mb-1 block text-sm font-medium">Bukti transfer (wajib untuk
                                            manual)</label><input type="file" name="refund_proof"
                                            accept="image/jpeg,image/png,image/webp,application/pdf"
                                            class="w-full text-sm"></div><button
                                        class="bg-primary-600 rounded-lg px-4 py-2.5 text-sm font-semibold text-white">Proses
                                        refund</button>
                                </form>
                                <form action="{{ route('order-returns.resolve', $order->returnRequest) }}"
                                    method="POST" class="space-y-3 rounded-lg border border-gray-200 p-4">@csrf<input
                                        type="hidden" name="resolution_type" value="replacement">
                                    <h4 class="font-semibold">Kirim barang pengganti</h4>
                                    <p class="text-xs text-gray-500">Menggunakan item dan jumlah dari pesanan asli.
                                        Stok akan dikurangi dan dicatat sebagai stock movement.</p>
                                    <div><label class="mb-1 block text-sm font-medium">Kurir</label><input
                                            name="replacement_courier" required
                                            class="w-full rounded-lg border-gray-300 text-sm"
                                            placeholder="JNE, J&T, SiCepat..."></div>
                                    <div><label class="mb-1 block text-sm font-medium">Nomor resi</label><input
                                            name="replacement_tracking_number" required
                                            class="w-full rounded-lg border-gray-300 text-sm"></div><button
                                        class="bg-primary-600 rounded-lg px-4 py-2.5 text-sm font-semibold text-white">Catat
                                        pengiriman replacement</button>
                                </form>
                            </div>
                        @elseif ($order->returnRequest->resolution_status === 'processing')
                            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm">
                                <p class="font-semibold">Penyelesaian:
                                    {{ ucfirst($order->returnRequest->resolution_type) }} · Processing</p>
                                @if ($order->returnRequest->resolution_type === 'refund')
                                    <p class="mt-1">Nominal: Rp
                                        {{ number_format($order->returnRequest->refund_amount, 0, ',', '.') }} ·
                                        {{ ucfirst($order->returnRequest->refund_method) }} · Referensi:
                                        {{ $order->returnRequest->refund_reference }}</p>
                                    @if ($order->returnRequest->refund_proof_path)
                                        <a class="text-primary-700 underline"
                                            href="{{ route('order-returns.refund-proof', $order->returnRequest) }}">Lihat
                                            bukti refund</a>
                                    @endif
                                    <p class="mt-2 text-xs">Verifikasi dana benar-benar telah dikembalikan sebelum
                                        menyelesaikan.</p>
                                @else<p class="mt-1">{{ $order->returnRequest->replacement_courier }} ·
                                        {{ $order->returnRequest->replacement_tracking_number }}</p>
                                    <p class="mt-2 text-xs">Pastikan barang pengganti sudah diterima pelanggan sebelum
                                        menyelesaikan.</p>
                                @endif
                                <form action="{{ route('order-returns.complete', $order->returnRequest) }}"
                                    method="POST" class="mt-3 flex flex-wrap gap-2">
                                    @csrf
                                    @if ($order->returnRequest->resolution_type === 'refund')
                                        <input name="resolution_reference"
                                            value="{{ $order->returnRequest->refund_reference }}" required
                                            class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm"
                                            placeholder="Referensi refund terverifikasi">
                                    @endif
                                    <button
                                        class="rounded-lg bg-green-700 px-4 py-2.5 text-sm font-semibold text-white">Konfirmasi
                                        penyelesaian</button>
                                </form>
                            </div>
                        @endif
                    @elseif ($order->returnRequest->status === 'completed')
                        <div class="mt-4 rounded-lg bg-green-50 p-3 text-sm text-green-800">Pengembalian selesai
                            melalui {{ ucfirst($order->returnRequest->resolution_type ?? 'proses admin') }}.</div>
                    @endif
                </div>
            </section>
        @endif
    </div>

</x-app-layout>
