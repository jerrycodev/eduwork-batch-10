<x-storefront-layout title="Pembayaran Berhasil | RevStore" description="Informasi status pembayaran pesanan RevStore.">
    <div class="container mx-auto max-w-2xl px-4 py-10 sm:py-16">
        <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
            <div class="px-6 py-10 text-center sm:px-10">
                @if ($verified)
                    <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-700"><i data-lucide="check-circle-2" class="h-8 w-8"></i></span>
                    <h1 class="mt-5 text-2xl font-bold tracking-tight text-gray-900">Pembayaran berhasil!</h1>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-600">Terima kasih. Pembayaran pesanan Anda telah kami terima dan terverifikasi.</p>
                @else
                    <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-amber-700"><i data-lucide="clock-3" class="h-8 w-8"></i></span>
                    <h1 class="mt-5 text-2xl font-bold tracking-tight text-gray-900">Pembayaran sedang diverifikasi</h1>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-600">Kami sedang menunggu konfirmasi resmi dari Midtrans. Mohon periksa kembali status pesanan Anda beberapa saat lagi.</p>
                @endif
                @if ($order)
                    <div class="mt-7 rounded-2xl bg-gray-50 p-4 text-left">
                        <div class="flex items-center justify-between gap-4 text-sm"><span class="text-gray-500">Nomor pesanan</span><strong class="text-gray-900">{{ $order->order_number }}</strong></div>
                        <div class="mt-2 flex items-center justify-between gap-4 text-sm"><span class="text-gray-500">Total pembayaran</span><strong class="text-gray-900">Rp {{ number_format((int) $order->total, 0, ',', '.') }}</strong></div>
                        <div class="mt-2 flex items-center justify-between gap-4 text-sm"><span class="text-gray-500">Status</span><strong class="{{ $verified ? 'text-emerald-700' : 'text-amber-700' }}">{{ ucfirst($order->payment_status) }}</strong></div>
                    </div>
                @endif
                <div class="mt-7 flex flex-col justify-center gap-3 sm:flex-row">
                    @if ($order && auth('customer')->check() && (string) auth('customer')->id() === (string) $order->user_id)
                        <a href="{{ route('orders.customer.show', $order) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">Lihat detail pesanan<i data-lucide="arrow-right" class="h-4 w-4"></i></a>
                    @endif
                    <a href="{{ route('home') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-5 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Kembali ke beranda</a>
                </div>
            </div>
        </section>
    </div>
</x-storefront-layout>
