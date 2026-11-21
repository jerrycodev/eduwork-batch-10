{{-- Dokumentasi: Daftar log pengiriman email transaksional dan aksi retry. --}}
<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-gray-900">Email Delivery Logs</h2>
            <p class="mt-1 text-sm text-gray-500">Riwayat email transaksi dan keamanan akun, status pengiriman, serta percobaan ulang.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <form method="GET" action="{{ route('email-delivery-logs.index') }}" class="grid gap-3 border-b border-gray-100 p-4 sm:grid-cols-2 lg:grid-cols-5">
                    <label class="lg:col-span-2">
                        <span class="mb-1 block text-xs font-semibold text-gray-500">Cari penerima, tipe, atau nomor pesanan</span>
                        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search email..." class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </label>
                    <label>
                        <span class="mb-1 block text-xs font-semibold text-gray-500">Status</span>
                        <select name="status" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="">Semua status</option>
                            @foreach (['queued' => 'Queued', 'sent' => 'Sent', 'failed' => 'Failed'] as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label><span class="mb-1 block text-xs font-semibold text-gray-500">Dari</span><input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"></label>
                    <label><span class="mb-1 block text-xs font-semibold text-gray-500">Sampai</span><input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"></label>
                    <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-5">
                        <button class="rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">Filter</button>
                        <a href="{{ route('email-delivery-logs.index') }}" class="rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50">Reset</a>
                    </div>
                </form>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1000px] divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500"><tr>
                            <th class="px-5 py-3.5 font-semibold">Waktu</th><th class="px-5 py-3.5 font-semibold">Penerima</th><th class="px-5 py-3.5 font-semibold">Jenis Email / Referensi</th><th class="px-5 py-3.5 font-semibold">Status</th><th class="px-5 py-3.5 font-semibold">Percobaan</th><th class="px-5 py-3.5 font-semibold">Aksi</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($logs as $log)
                                <tr class="align-top hover:bg-gray-50/70">
                                    <td class="whitespace-nowrap px-5 py-4 text-gray-500">{{ $log->created_at->format('d M Y, H:i:s') }}</td>
                                    <td class="px-5 py-4 text-gray-900">{{ $log->recipient }}</td>
                                    <td class="px-5 py-4"><p class="font-medium text-gray-900">{{ ['email_change_confirmation' => 'Konfirmasi perubahan email', 'email_changed_notification' => 'Notifikasi perubahan email'][$log->type] ?? str($log->type)->replace('_', ' ')->title() }}</p><p class="mt-1 text-xs text-gray-500">{{ $log->order?->order_number ?? ($log->type === 'email_change_confirmation' ? 'Verifikasi alamat email baru' : ($log->type === 'email_changed_notification' ? 'Pemberitahuan keamanan akun' : 'Email non-pesanan')) }}</p></td>
                                    <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ ['sent' => 'bg-green-50 text-green-700', 'failed' => 'bg-red-50 text-red-700', 'queued' => 'bg-amber-50 text-amber-700'][$log->status] }}">{{ strtoupper($log->status) }}</span>
                                        @if ($log->last_error)<p class="mt-2 max-w-sm whitespace-normal text-xs text-red-600" title="{{ $log->last_error }}">{{ str($log->last_error)->limit(180) }}</p>@endif
                                        @if ($log->sent_at)<p class="mt-1 text-xs text-gray-400">{{ $log->sent_at->format('d M Y H:i:s') }}</p>@endif
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-gray-600">{{ $log->attempt_count }} attempt · {{ $log->retry_count }} retry</td>
                                    <td class="px-5 py-4">@if ($log->status === 'failed' && $log->order_id)
                                        <form method="POST" action="{{ route('email-delivery-logs.retry', $log) }}" x-data="adminConfirm(@js('Jadwalkan ulang pengiriman email ini?'))" @submit="submit($event)">@csrf<button class="rounded-lg border border-primary-200 px-3 py-2 text-xs font-semibold text-primary-700 hover:bg-primary-50">Retry email</button></form>
                                    @elseif ($log->status === 'failed')<span class="text-xs text-gray-400" title="Pengiriman ulang email keamanan belum tersedia">Retry tidak tersedia</span>
                                    @else<span class="text-xs text-gray-400">—</span>@endif</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-gray-500">Belum ada email yang tercatat.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($logs->hasPages())<div class="border-t border-gray-100 px-5 py-4">{{ $logs->links() }}</div>@endif
            </section>
            <p class="text-xs text-gray-500">Status Sent berarti mail transport menerima email. Status ini tidak menjamin email telah masuk ke inbox penerima.</p>
        </div>
    </div>
</x-app-layout>
