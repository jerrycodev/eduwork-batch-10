<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detail Order #{{ $order->id }}</h2>
            <a href="{{ route('orders.index') }}" class="text-sm text-indigo-600 underline">&larr; Kembali ke Orders</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8 lg:py-10">
        <div class="mx-auto grid w-full max-w-7xl grid-cols-1 items-start gap-6 px-4 sm:px-6 lg:px-8 lg:grid-cols-3">
            <section class="overflow-hidden bg-white shadow-sm sm:rounded-lg lg:col-span-2">
                <div class="border-b px-6 py-4">
                    <h3 class="font-semibold text-gray-800">Order Items</h3>
                </div>
                <div class="w-full overflow-x-auto">
                    <table class="table min-w-[560px]">
                        <thead>
                            <tr class="table-row">
                                <th class="table-cell">Product</th>
                                <th class="table-cell">Price</th>
                                <th class="table-cell">Quantity</th>
                                <th class="table-cell">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($order->orderItems as $item)
                                <tr class="table-row">
                                    <td class="table-cell">{{ $item->product_name }}</td>
                                    <td class="table-cell">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                    <td class="table-cell">{{ $item->quantity }}</td>
                                    <td class="table-cell">Rp {{ number_format($item->total, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr class="table-row"><td class="table-cell text-center" colspan="4">Tidak ada item pesanan.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="table-row">
                                <th class="table-cell text-right" colspan="3">Total</th>
                                <th class="table-cell">Rp {{ number_format($order->total, 0, ',', '.') }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>

            <aside class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="border-b px-6 py-4"><h3 class="font-semibold text-gray-800">Customer Information</h3></div>
                <dl class="space-y-4 px-6 py-5 text-sm">
                    <div><dt class="text-gray-500">Customer</dt><dd class="mt-1 font-medium text-gray-900">{{ $order->user_name }}</dd></div>
                    <div><dt class="text-gray-500">Email</dt><dd class="mt-1 break-all font-medium text-gray-900">{{ $order->user_email }}</dd></div>
                    <div><dt class="text-gray-500">Phone</dt><dd class="mt-1 font-medium text-gray-900">{{ $order->user_phone ?: 'Belum diisi' }}</dd></div>
                    <div><dt class="text-gray-500">Shipping Address</dt><dd class="mt-1 font-medium text-gray-900">{{ collect([$order->address, $order->city, $order->state, $order->zip, $order->country])->filter()->join(', ') ?: 'Belum diisi' }}</dd></div>
                    <div><dt class="text-gray-500">Order Date</dt><dd class="mt-1 font-medium text-gray-900">{{ $order->created_at->format('d M Y, H:i') }}</dd></div>
                    <div><dt class="text-gray-500">Status</dt><dd class="mt-1 font-medium text-gray-900">{{ ucfirst($order->status) }}</dd></div>
                </dl>
            </aside>
        </div>
    </div>
</x-app-layout>
