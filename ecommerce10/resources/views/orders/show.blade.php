<x-layout-component>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('orders.customer.index') }}" class="text-sm text-muted-foreground hover:text-foreground">&larr; Kembali ke pesanan</a>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-foreground sm:text-3xl">Pesanan #{{ $order->id }}</h1>
            <p class="mt-1 text-sm text-muted-foreground">Dibuat {{ $order->created_at->format('d M Y, H:i') }}</p>
        </div>
        <span class="badge" data-variant="secondary">{{ ucfirst($order->status) }}</span>
    </div>

    @if (session('success'))
        <div class="mb-5 rounded-lg border border-green-500/30 bg-green-500/10 px-4 py-3 text-sm text-green-700" role="status">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
        <section class="card min-w-0 lg:col-span-8">
            <div class="card-header border-b border-gray-200"><h2 class="card-title">Item Pesanan</h2></div>
            <div class="card-content p-0 divide-y divide-border divide-gray-200">
                @foreach ($order->orderItems as $item)
                    <div class="flex items-start justify-between gap-4 p-4">
                        <div class="min-w-0">
                            <p class="font-semibold text-foreground">{{ $item->product_name }}</p>
                            <p class="mt-1 text-sm text-muted-foreground">{{ $item->quantity }} × Rp {{ number_format($item->price, 0, ',', '.') }}</p>
                        </div>
                        <p class="shrink-0 font-semibold text-foreground">Rp {{ number_format($item->total, 0, ',', '.') }}</p>
                    </div>
                @endforeach
            </div>
            <div class="card-footer border-t border-gray-200 flex items-center justify-between gap-4">
                <span class="font-semibold text-foreground">Total Pesanan</span>
                <span class="text-lg font-bold text-foreground">Rp {{ number_format($order->total, 0, ',', '.') }}</span>
            </div>
        </section>

        <aside class="card min-w-0 lg:col-span-4">
            <div class="card-header border-b border-gray-200"><h2 class="card-title">Informasi Pemesan</h2></div>
            <div class="card-content space-y-3 py-5 text-sm">
                <div><p class="text-muted-foreground">Nama</p><p class="font-medium text-foreground">{{ $order->user_name }}</p></div>
                <div><p class="text-muted-foreground">Email</p><p class="break-all font-medium text-foreground">{{ $order->user_email }}</p></div>
                <div><p class="text-muted-foreground">Telepon</p><p class="font-medium text-foreground">{{ $order->user_phone ?: 'Belum diisi' }}</p></div>
                <div><p class="text-muted-foreground">Alamat pengiriman</p><p class="font-medium text-foreground">{{ collect([$order->address, $order->city, $order->state, $order->zip, $order->country])->filter()->join(', ') ?: 'Belum diisi' }}</p></div>
            </div>
        </aside>
    </div>
</x-layout-component>
