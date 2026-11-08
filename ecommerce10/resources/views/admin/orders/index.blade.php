<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Orders') }}</h2>
    </x-slot>

    <div class="py-6 sm:py-8 lg:py-10">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <x-alert class="bg-green-200 border-green-400">{{ session('success') }}</x-alert>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="w-full overflow-x-auto">
                    <table class="table min-w-[760px]">
                        <thead>
                            <tr class="table-row">
                                <th class="table-cell">Order ID</th>
                                <th class="table-cell">Customer</th>
                                <th class="table-cell">Email</th>
                                <th class="table-cell">Date</th>
                                <th class="table-cell">Items</th>
                                <th class="table-cell">Total</th>
                                <th class="table-cell">Status</th>
                                <th class="table-cell" style="text-align:right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($orders as $order)
                                <tr class="table-row">
                                    <td class="table-cell">#{{ $order->id }}</td>
                                    <td class="table-cell">{{ $order->user_name }}</td>
                                    <td class="table-cell">{{ $order->user_email }}</td>
                                    <td class="table-cell">{{ $order->created_at->format('d M Y, H:i') }}</td>
                                    <td class="table-cell">{{ $order->order_items_count }}</td>
                                    <td class="table-cell">Rp {{ number_format($order->total, 0, ',', '.') }}</td>
                                    <td class="table-cell">{{ ucfirst($order->status) }}</td>
                                    <td class="table-cell">
                                        <div class="flex justify-end">
                                            <a href="{{ route('orders.show', $order) }}" class="text-indigo-400 underline">Detail</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr class="table-row">
                                    <td class="table-cell text-center" colspan="8">Belum ada pesanan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $orders->links() }}</div>
        </div>
    </div>
</x-app-layout>
