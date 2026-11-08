<x-layout-component>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 sm:mb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">Pesanan Saya</h1>
            <p class="mt-1 text-sm text-muted-foreground">Riwayat pesanan yang sudah tercatat.</p>
        </div>
        <a href="{{ route('shop') }}" class="btn" data-variant="outline">Lanjut Belanja</a>
    </div>

    @if (session('success'))
        <div class="mb-5 rounded-lg border border-green-500/30 bg-green-500/10 px-4 py-3 text-sm text-green-700" role="status">{{ session('success') }}</div>
    @endif

    @if ($orders->isEmpty())
        <div class="card flex flex-col items-center px-5 py-12 text-center">
            <i data-lucide="package-search" class="mb-4 h-10 w-10 text-muted-foreground"></i>
            <h2 class="font-semibold text-foreground">Belum ada pesanan</h2>
            <p class="mt-2 text-sm text-muted-foreground">Pesanan yang dibuat akan muncul di halaman ini.</p>
            <a href="{{ route('shop') }}" class="btn mt-5" data-variant="default">Mulai Belanja</a>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-card">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">No. Pesanan</th>
                            <th class="px-4 py-3 font-medium">Tanggal</th>
                            <th class="px-4 py-3 font-medium">Item</th>
                            <th class="px-4 py-3 font-medium">Total</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border divide-gray-200">
                        @foreach ($orders as $order)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-foreground">#{{ $order->id }}</td>
                                <td class="px-4 py-3 text-muted-foreground">{{ $order->created_at->format('d M Y, H:i') }}</td>
                                <td class="px-4 py-3 text-foreground">{{ $order->order_items_count }}</td>
                                <td class="px-4 py-3 font-semibold text-foreground">Rp {{ number_format($order->total, 0, ',', '.') }}</td>
                                <td class="px-4 py-3"><span class="badge" data-variant="secondary">{{ ucfirst($order->status) }}</span></td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('orders.customer.show', $order) }}" class="font-medium text-primary hover:underline">Lihat</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-5">{{ $orders->links() }}</div>
    @endif
</x-layout-component>
