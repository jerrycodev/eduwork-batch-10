{{-- Dokumentasi: Dashboard pemeriksaan dan pemulihan status shipment Biteship. --}}
<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-gray-900">Biteship Audit</h2>
            <p class="mt-1 text-sm text-gray-500">Audit kesiapan shipment, catat kegagalan API, dan ulangi pembuatan pengiriman dengan jejak pemeriksaan.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))<div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>@endif
            @if (session('error'))<div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>@endif

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                @foreach ([['Pesanan perlu shipment', $counts['problem_orders'], 'text-amber-700'], ['Terblokir', $counts['blocked'], 'text-red-700'], ['Siap', $counts['ready'], 'text-blue-700'], ['Berhasil', $counts['created'], 'text-green-700'], ['Gagal', $counts['error'], 'text-red-700']] as [$label, $value, $color])
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"><p class="text-sm text-gray-500">{{ $label }}</p><p class="mt-1 text-2xl font-bold {{ $color }}">{{ $value }}</p></div>
                @endforeach
            </div>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4"><h3 class="font-semibold text-gray-900">Pesanan lunas yang belum memiliki shipment</h3><p class="mt-1 text-xs text-gray-500">Maksimal 500 pesanan terbaru dengan status Processing dan belum memiliki ID Biteship.</p></div>
                <form method="GET" action="{{ route('biteship-reconciliation.index') }}" class="border-b border-gray-100 p-4"><label class="block"><span class="mb-1 block text-xs font-semibold text-gray-500">Cari nomor pesanan atau email</span><input type="search" name="search" value="{{ request('search') }}" placeholder="ORD-..." class="w-full rounded-lg border-gray-300 text-sm shadow-sm"></label><div class="mt-3"><button class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Cari</button><a href="{{ route('biteship-reconciliation.index') }}" class="ml-2 text-sm text-gray-500">Reset</a></div></form>
                <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-100 text-sm"><thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-5 py-3">Pesanan</th><th class="px-5 py-3">Penerima</th><th class="px-5 py-3">Kurir / Layanan</th><th class="px-5 py-3">Pembayaran</th><th class="px-5 py-3">Audit terakhir</th><th class="px-5 py-3">Aksi</th></tr></thead><tbody class="divide-y divide-gray-100">
                    @forelse ($problemOrders as $order)
                        @php($lastCheck = $order->biteshipReconciliationChecks->first())
                        <tr><td class="px-5 py-4"><a href="{{ route('orders.show', $order) }}" class="font-semibold text-indigo-700 hover:underline">{{ $order->order_number }}</a><p class="mt-1 text-xs text-gray-500">{{ $order->created_at?->format('d M Y H:i') }}</p></td><td class="px-5 py-4">{{ $order->recipient_name }}<p class="text-xs text-gray-500">{{ $order->zip }}</p></td><td class="px-5 py-4">{{ $order->shipping_courier }} · {{ $order->shipping_service }}<p class="text-xs text-gray-500">{{ $order->shipping_courier_code }} / {{ $order->shipping_service_code }}</p></td><td class="px-5 py-4"><span class="rounded-full bg-green-50 px-2 py-1 text-xs font-semibold text-green-700">Paid</span><p class="mt-1 text-xs text-gray-500">Rp{{ number_format($order->total, 0, ',', '.') }}</p></td><td class="max-w-xs px-5 py-4">@if ($lastCheck)<span class="text-xs font-semibold">{{ strtoupper($lastCheck->result) }}</span><p class="mt-1 line-clamp-2 text-xs text-gray-500">{{ $lastCheck->message }}</p>@else<span class="text-xs text-gray-400">Belum pernah diaudit</span>@endif</td><td class="px-5 py-4"><div class="flex min-w-40 flex-col gap-2"><form method="POST" action="{{ route('biteship-reconciliation.check', $order) }}">@csrf<button class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700">Periksa kesiapan</button></form><form method="POST" action="{{ route('biteship-reconciliation.retry', $order) }}" x-data="adminConfirm(@js('Buat shipment Biteship untuk pesanan ini?'))" @submit="submit($event)">@csrf<button class="w-full rounded-lg bg-amber-700 px-3 py-2 text-xs font-semibold text-white">Buat shipment</button></form></div></td></tr>
                    @empty<tr><td colspan="6" class="px-5 py-10 text-center text-sm text-gray-500">Tidak ada pesanan lunas Processing yang tertunda membuat shipment.</td></tr>@endforelse
                </tbody></table></div>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h3 class="font-semibold text-gray-900">Webhook masuk dari Biteship</h3>
                    <p class="mt-1 text-xs text-gray-500">100 event terbaru, termasuk signature tidak valid, referensi tidak dikenal, handshake, dan kegagalan pemrosesan. Payload hanya menyimpan field integrasi yang aman.</p>
                    <div class="mt-3 flex flex-wrap gap-2 text-xs">
                        <span class="rounded-full bg-green-50 px-2.5 py-1 font-semibold text-green-700">Processed: {{ $webhookCounts['processed'] }}</span>
                        <span class="rounded-full bg-red-50 px-2.5 py-1 font-semibold text-red-700">Rejected: {{ $webhookCounts['rejected'] }}</span>
                        <span class="rounded-full bg-amber-50 px-2.5 py-1 font-semibold text-amber-700">Failed: {{ $webhookCounts['failed'] }}</span>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 font-semibold text-gray-700">Handshake: {{ $webhookCounts['handshake'] }}</span>
                    </div>
                </div>
                <form method="GET" action="{{ route('biteship-reconciliation.index') }}" class="flex flex-wrap items-end gap-3 border-b border-gray-100 p-4">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <label><span class="mb-1 block text-xs font-semibold text-gray-500">Hasil webhook</span><select name="webhook_result" class="rounded-lg border-gray-300 text-sm"><option value="">Semua hasil</option>@foreach (['processed' => 'Processed', 'rejected' => 'Rejected', 'failed' => 'Failed', 'handshake' => 'Handshake'] as $value => $label)<option value="{{ $value }}" @selected(request('webhook_result') === $value)>{{ $label }}</option>@endforeach</select></label>
                    <label><span class="mb-1 block text-xs font-semibold text-gray-500">Event</span><select name="webhook_event" class="rounded-lg border-gray-300 text-sm"><option value="">Semua event</option>@foreach ($webhookLogs->pluck('event')->filter()->unique()->sort() as $event)<option value="{{ $event }}" @selected(request('webhook_event') === $event)>{{ $event }}</option>@endforeach</select></label>
                    <button class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Filter webhook</button>
                </form>
                <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-5 py-3">Diterima</th><th class="px-5 py-3">Event</th><th class="px-5 py-3">Pesanan / Biteship ID</th><th class="px-5 py-3">Status kurir</th><th class="px-5 py-3">Signature</th><th class="px-5 py-3">Hasil / HTTP</th><th class="px-5 py-3">Detail</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($webhookLogs as $webhook)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-4 text-xs text-gray-500">{{ $webhook->received_at?->format('d M Y H:i:s') }}</td>
                                <td class="px-5 py-4"><span class="font-semibold text-gray-800">{{ $webhook->event }}</span></td>
                                <td class="px-5 py-4">@if ($webhook->order)<a href="{{ route('orders.show', $webhook->order) }}" class="font-semibold text-indigo-700 hover:underline">{{ $webhook->order->order_number }}</a>@else<span class="text-gray-400">Tidak terhubung</span>@endif<p class="mt-1 font-mono text-xs text-gray-500">{{ $webhook->biteship_order_id ?: $webhook->reference_id ?: '—' }}</p></td>
                                <td class="px-5 py-4">{{ $webhook->remote_status ? str($webhook->remote_status)->replace('_', ' ')->title() : '—' }}</td>
                                <td class="px-5 py-4">@if (is_null($webhook->signature_valid))<span class="text-xs text-gray-400">N/A</span>@elseif ($webhook->signature_valid)<span class="text-xs font-semibold text-green-700">Valid</span>@else<span class="text-xs font-semibold text-red-700">Invalid</span>@endif</td>
                                <td class="px-5 py-4"><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $webhook->result === 'processed' || $webhook->result === 'handshake' ? 'bg-green-50 text-green-700' : ($webhook->result === 'failed' || $webhook->result === 'rejected' ? 'bg-red-50 text-red-700' : 'bg-gray-100 text-gray-700') }}">{{ strtoupper($webhook->result) }}</span><p class="mt-1 text-xs text-gray-500">HTTP {{ $webhook->http_status ?? '—' }}</p></td>
                                <td class="max-w-sm px-5 py-4"><p class="text-xs text-gray-600">{{ $webhook->message }}</p>@if ($webhook->payload)<details class="mt-2"><summary class="cursor-pointer text-xs font-semibold text-indigo-700">Lihat payload aman</summary><pre class="mt-2 max-h-48 overflow-auto rounded bg-gray-50 p-2 text-[11px] text-gray-600">{{ json_encode($webhook->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></details>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-8 text-center text-sm text-gray-500">Belum ada webhook yang tercatat.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4"><h3 class="font-semibold text-gray-900">Riwayat pemeriksaan dan percobaan</h3><p class="mt-1 text-xs text-gray-500">50 aktivitas terakhir. Detail error API dicatat tanpa menyimpan API key atau payload alamat lengkap.</p></div>
                <form method="GET" action="{{ route('biteship-reconciliation.index') }}" class="flex flex-wrap items-end gap-3 border-b border-gray-100 p-4"><input type="hidden" name="search" value="{{ request('search') }}"><label><span class="mb-1 block text-xs font-semibold text-gray-500">Hasil</span><select name="result" class="rounded-lg border-gray-300 text-sm"><option value="">Semua</option>@foreach (['blocked' => 'Blocked', 'ready' => 'Ready', 'created' => 'Created', 'error' => 'Error'] as $value => $label)<option value="{{ $value }}" @selected(request('result') === $value)>{{ $label }}</option>@endforeach</select></label><button class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Filter riwayat</button></form>
                <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-100 text-sm"><thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-5 py-3">Waktu</th><th class="px-5 py-3">Pesanan</th><th class="px-5 py-3">Hasil</th><th class="px-5 py-3">HTTP</th><th class="px-5 py-3">ID Biteship</th><th class="px-5 py-3">Pesan</th><th class="px-5 py-3">Admin</th></tr></thead><tbody class="divide-y divide-gray-100">
                    @forelse ($recentChecks as $check)<tr><td class="whitespace-nowrap px-5 py-4 text-xs text-gray-500">{{ $check->checked_at?->format('d M Y H:i:s') }}</td><td class="px-5 py-4"><a href="{{ route('orders.show', $check->order) }}" class="font-semibold text-indigo-700 hover:underline">{{ $check->order->order_number }}</a></td><td class="px-5 py-4"><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $check->result === 'created' ? 'bg-green-50 text-green-700' : ($check->result === 'error' || $check->result === 'blocked' ? 'bg-red-50 text-red-700' : 'bg-blue-50 text-blue-700') }}">{{ strtoupper($check->result) }}</span></td><td class="px-5 py-4">{{ $check->http_status ?? '—' }}</td><td class="px-5 py-4 font-mono text-xs">{{ $check->biteship_order_id ?? '—' }}</td><td class="max-w-xl whitespace-pre-line px-5 py-4 text-xs text-gray-600">{{ $check->message }}</td><td class="px-5 py-4 text-xs text-gray-500">{{ $check->checker?->name ?? 'System' }}</td></tr>
                    @empty<tr><td colspan="7" class="px-5 py-8 text-center text-sm text-gray-500">Belum ada riwayat audit Biteship.</td></tr>@endforelse
                </tbody></table></div>
            </section>
        </div>
    </div>
</x-app-layout>
