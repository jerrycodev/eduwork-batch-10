<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-gray-900">Payment Reconciliation</h2>
            <p class="mt-1 text-sm text-gray-500">Deteksi ketidaksesuaian pesanan dan verifikasi status transaksi langsung ke Midtrans.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))<div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>@endif
            @if (session('error'))<div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>@endif

            <div class="grid gap-3 sm:grid-cols-3">
                @foreach (['critical' => ['Kritis', 'text-red-700', 'bg-red-50'], 'warning' => ['Peringatan', 'text-amber-700', 'bg-amber-50'], 'info' => ['Informasi', 'text-blue-700', 'bg-blue-50']] as $severity => [$label, $color, $background])
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"><p class="text-sm text-gray-500">{{ $label }}</p><p class="mt-1 text-2xl font-bold {{ $color }}">{{ $counts->get($severity, 0) }}</p><p class="mt-1 text-xs text-gray-400">Temuan terdeteksi</p></div>
                @endforeach
            </div>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-3 text-xs text-gray-500">Pemindaian lokal mencakup maksimal 1.000 pesanan terbaru. Gunakan Check Midtrans untuk verifikasi transaksi aktual.</div>
                <form method="GET" action="{{ route('payment-reconciliation.index') }}" class="grid gap-3 border-b border-gray-100 p-4 sm:grid-cols-3">
                    <label class="sm:col-span-2"><span class="mb-1 block text-xs font-semibold text-gray-500">Cari nomor pesanan atau email</span><input type="search" name="search" value="{{ request('search') }}" placeholder="ORD-..." class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></label>
                    <label><span class="mb-1 block text-xs font-semibold text-gray-500">Severity</span><select name="severity" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"><option value="">Semua temuan</option>@foreach (['critical' => 'Kritis', 'warning' => 'Peringatan', 'info' => 'Informasi'] as $value => $label)<option value="{{ $value }}" @selected(request('severity') === $value)>{{ $label }}</option>@endforeach</select></label>
                    <div class="flex gap-2 sm:col-span-3"><button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Filter</button><a href="{{ route('payment-reconciliation.index') }}" class="rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50">Reset</a></div>
                </form>
                <div class="divide-y divide-gray-100">
                    @forelse ($findings as $finding)
                        <article class="p-5">
                            <div class="flex flex-col justify-between gap-3 lg:flex-row lg:items-start">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ ['critical' => 'bg-red-50 text-red-700', 'warning' => 'bg-amber-50 text-amber-700', 'info' => 'bg-blue-50 text-blue-700'][$finding['severity']] }}">{{ strtoupper($finding['severity']) }}</span><span class="text-xs text-gray-400">{{ $finding['code'] }}</span></div>
                                    <h3 class="mt-2 font-semibold text-gray-900">{{ $finding['title'] }}</h3>
                                    <p class="mt-1 text-sm text-gray-600">{{ $finding['detail'] }}</p>
                                    <p class="mt-2 text-sm text-gray-500"><span class="font-semibold">Tindak lanjut:</span> {{ $finding['action'] }}</p>
                                    <p class="mt-2 text-xs text-gray-400">{{ $finding['order']->order_number }} · {{ $finding['order']->user_email }} · Rp{{ number_format($finding['order']->total, 0, ',', '.') }} · payment: {{ $finding['order']->payment_status }} · order: {{ $finding['order']->status }}</p>
                                </div>
                                <div class="flex shrink-0 flex-col gap-2">
                                    <a href="{{ route('orders.show', $finding['order']) }}" class="rounded-lg border border-gray-200 px-3 py-2 text-center text-xs font-semibold text-gray-700 hover:bg-gray-50">Detail pesanan</a>
                                    @if ($finding['order']->payment_order_id)
                                        <form method="POST" action="{{ route('payment-reconciliation.check', $finding['order']) }}" onsubmit="return confirm('Periksa status transaksi pesanan ini langsung ke Midtrans?')">@csrf<button class="w-full rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Check Midtrans</button></form>
                                    @endif
                                    @if ($finding['order']->payment_order_id && in_array($finding['order']->payment_status, ['pending', 'challenge', 'failed', 'cancelled', 'expired'], true))
                                        <details class="max-w-xs rounded-lg border border-amber-200 bg-amber-50 p-3 text-left">
                                            <summary class="cursor-pointer text-xs font-semibold text-amber-800">Sinkronkan ke Paid</summary>
                                            <p class="mt-2 text-xs text-amber-800">Sistem akan mengambil ulang status Midtrans dan hanya melanjutkan jika order ID, nominal, serta status pembayaran berhasil cocok. Fulfillment tidak berubah.</p>
                                            <form method="POST" action="{{ route('payment-reconciliation.resolve', $finding['order']) }}" class="mt-3 space-y-2">@csrf
                                                <input type="hidden" name="expected_local_status" value="{{ $finding['order']->payment_status }}">
                                                <label class="block text-xs font-medium text-gray-700">Alasan koreksi<textarea name="reason" required minlength="10" maxlength="1000" rows="3" class="mt-1 w-full rounded-md border-gray-300 text-xs" placeholder="Jelaskan hasil verifikasi dan alasan sinkronisasi"></textarea></label>
                                                <label class="flex items-start gap-2 text-xs text-gray-700"><input type="checkbox" name="confirm" value="1" required class="mt-0.5 rounded border-gray-300"><span>Saya sudah memeriksa dan menyetujui perubahan status pembayaran menjadi paid.</span></label>
                                                <button class="w-full rounded-lg bg-amber-700 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-800">Verifikasi & Terapkan</button>
                                            </form>
                                        </details>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="px-5 py-12 text-center"><p class="font-semibold text-gray-900">Tidak ada temuan untuk filter ini</p><p class="mt-1 text-sm text-gray-500">Pemeriksaan lokal tidak menemukan anomali pada pesanan yang diperiksa.</p></div>
                    @endforelse
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4"><h3 class="font-semibold text-gray-900">Riwayat pemeriksaan Midtrans</h3><p class="mt-1 text-xs text-gray-500">Pemeriksaan hanya membandingkan data; tidak mengubah status pembayaran atau pesanan.</p></div>
                <div class="overflow-x-auto"><table class="w-full min-w-[800px] divide-y divide-gray-200 text-left text-sm"><thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-5 py-3">Waktu</th><th class="px-5 py-3">Pesanan</th><th class="px-5 py-3">Status lokal</th><th class="px-5 py-3">Status Midtrans</th><th class="px-5 py-3">Hasil</th><th class="px-5 py-3">Catatan</th></tr></thead><tbody class="divide-y divide-gray-100">
                    @forelse ($recentChecks as $check)<tr><td class="whitespace-nowrap px-5 py-3 text-gray-500">{{ $check->checked_at->format('d M Y H:i:s') }}</td><td class="px-5 py-3 font-medium text-gray-900">{{ $check->order?->order_number ?? '—' }}</td><td class="px-5 py-3">{{ $check->local_status ?? '—' }}</td><td class="px-5 py-3">{{ $check->remote_status ?? '—' }}</td><td class="px-5 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ ['matched' => 'bg-green-50 text-green-700', 'mismatch' => 'bg-amber-50 text-amber-700', 'error' => 'bg-red-50 text-red-700'][$check->result] }}">{{ strtoupper($check->result) }}</span></td><td class="max-w-sm whitespace-normal px-5 py-3 text-xs text-gray-500">{{ $check->message }}</td></tr>
                    @empty<tr><td colspan="6" class="px-5 py-8 text-center text-sm text-gray-500">Belum ada pemeriksaan Midtrans.</td></tr>@endforelse
                </tbody></table></div>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4"><h3 class="font-semibold text-gray-900">Riwayat koreksi status pembayaran</h3><p class="mt-1 text-xs text-gray-500">Audit perubahan status yang diterapkan admin setelah verifikasi langsung ke Midtrans.</p></div>
                <div class="overflow-x-auto"><table class="w-full min-w-[900px] divide-y divide-gray-200 text-left text-sm"><thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-5 py-3">Waktu</th><th class="px-5 py-3">Pesanan</th><th class="px-5 py-3">Admin</th><th class="px-5 py-3">Perubahan</th><th class="px-5 py-3">Midtrans</th><th class="px-5 py-3">Alasan</th></tr></thead><tbody class="divide-y divide-gray-100">
                    @forelse ($recentResolutions as $resolution)<tr><td class="whitespace-nowrap px-5 py-3 text-gray-500">{{ $resolution->resolved_at->format('d M Y H:i:s') }}</td><td class="px-5 py-3 font-medium text-gray-900">{{ $resolution->order?->order_number ?? '—' }}</td><td class="px-5 py-3">{{ $resolution->admin?->name ?? 'Admin tidak tersedia' }}</td><td class="px-5 py-3">{{ $resolution->previous_status }} → <span class="font-semibold text-green-700">{{ $resolution->new_status }}</span></td><td class="px-5 py-3">{{ $resolution->remote_status }} · Rp{{ number_format((float) $resolution->remote_amount, 0, ',', '.') }}</td><td class="max-w-sm whitespace-normal px-5 py-3 text-xs text-gray-500">{{ $resolution->reason }}</td></tr>
                    @empty<tr><td colspan="6" class="px-5 py-8 text-center text-sm text-gray-500">Belum ada koreksi status pembayaran.</td></tr>@endforelse
                </tbody></table></div>
            </section>
        </div>
    </div>
</x-app-layout>
