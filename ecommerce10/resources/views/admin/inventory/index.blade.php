{{-- Dokumentasi: Halaman pemantauan stok dan pencatatan pergerakan inventori. --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold tracking-tight text-gray-900">Inventory Management</h2>
                <p class="mt-1 text-sm text-gray-500">Manage stock in, stock out, adjustments, and movement history.</p>
            </div>
            <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-primary-600 hover:text-primary-700"><i data-lucide="package" class="h-4 w-4"></i> Manage products</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="flex w-full flex-col gap-6 px-4 sm:px-6 lg:px-8">
            @if (session('success')) <x-alert type="success">{{ session('success') }}</x-alert> @endif
            @if ($errors->any()) <x-alert type="error">{{ $errors->first() }}</x-alert> @endif

            <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                @foreach ([['Products', $summary['products'], 'package'], ['Total units', number_format($summary['units']), 'boxes'], ['Low stock (1–5)', $summary['low_stock'], 'alert-triangle'], ['Out of stock', $summary['out_of_stock'], 'package-x']] as [$label, $value, $icon])
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
                        <div class="flex items-center justify-between gap-2"><p class="text-sm font-medium text-gray-500">{{ $label }}</p><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-50 text-primary-600"><i data-lucide="{{ $icon }}" class="h-4 w-4"></i></span></div>
                        <p class="mt-3 text-2xl font-bold tracking-tight text-gray-900">{{ $value }}</p>
                    </div>
                @endforeach
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h3 class="font-semibold text-gray-900">Product stock</h3>
                    <p class="mt-1 text-sm text-gray-500">Use Stock In to restock, Stock Out to record damaged or missing units, or Adjustment to set the exact stock count.</p>
                </div>
                <form method="GET" action="{{ route('inventory.index') }}" class="flex flex-col gap-3 border-b border-gray-100 p-4 sm:flex-row sm:items-center">
                    <label class="relative flex-1"><span class="sr-only">Search product</span><i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i><input type="search" name="search" value="{{ request('search') }}" placeholder="Search products..." class="w-full rounded-lg border-gray-300 py-2.5 pl-9 pr-3 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"></label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-600"><input type="checkbox" name="low_stock" value="1" @checked(request()->boolean('low_stock')) class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"> Low stock only</label>
                    <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-700"><i data-lucide="filter" class="h-4 w-4"></i>Filter</button>
                    @if (request()->hasAny(['search', 'low_stock'])) <a href="{{ route('inventory.index') }}" class="text-center text-sm font-semibold text-gray-500 hover:text-gray-700">Reset</a> @endif
                </form>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1050px] divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-5 py-3.5 font-semibold">Product</th><th class="px-5 py-3.5 font-semibold">Category</th><th class="px-5 py-3.5 font-semibold">Current stock</th><th class="px-5 py-3.5 font-semibold">Record movement</th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($products as $product)
                                <tr class="align-top hover:bg-gray-50/70">
                                    <td class="px-5 py-4"><p class="font-semibold text-gray-900">{{ $product->name }}</p><p class="mt-1 text-xs text-gray-500">SKU {{ $product->sku }}</p></td>
                                    <td class="px-5 py-4 text-gray-600">{{ $product->category?->name ?? '—' }}</td>
                                    <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $product->stock === 0 ? 'bg-red-50 text-red-700' : ($product->stock <= 5 ? 'bg-amber-50 text-amber-700' : 'bg-green-50 text-green-700') }}">{{ number_format($product->stock) }} units</span></td>
                                    <td class="px-5 py-4">
                                        <form action="{{ route('inventory.movements.store', $product) }}" method="POST" class="grid grid-cols-1 gap-2 sm:grid-cols-[minmax(170px,1.2fr)_130px_100px_minmax(160px,1fr)_auto]">
                                            @csrf
                                            <select name="product_variant_id" aria-label="Variant for {{ $product->name }}" class="rounded-lg border-gray-300 text-xs shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                                @foreach ($product->variants as $variant)
                                                    <option value="{{ $variant->id }}" @selected($variant->is_default)>{{ $variant->name ?: $variant->sku }} · {{ $variant->sku }} ({{ $variant->stock }})</option>
                                                @endforeach
                                            </select>
                                            <select name="type" aria-label="Movement type for {{ $product->name }}" class="rounded-lg border-gray-300 text-xs shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                                <option value="in">Stock In (+)</option><option value="out">Stock Out (−)</option><option value="adjustment">Set exact stock</option>
                                            </select>
                                            <input name="quantity" type="number" min="0" max="1000000" required placeholder="Quantity" aria-label="Quantity for {{ $product->name }}" class="rounded-lg border-gray-300 text-xs shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                            <input name="notes" type="text" maxlength="2000" placeholder="Notes / reason (optional)" aria-label="Notes for {{ $product->name }}" class="rounded-lg border-gray-300 text-xs shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                            <button type="submit" class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-primary-600 px-3 py-2 text-xs font-semibold text-white hover:bg-primary-700"><i data-lucide="plus" class="h-3.5 w-3.5"></i>Save</button>
                                        </form>
                                        <p class="mt-1.5 text-[11px] text-gray-400">For “Set exact stock”, enter the new total quantity, not the difference.</p>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-12 text-center text-sm text-gray-500">No products found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($products->hasPages()) <div class="border-t border-gray-100 px-5 py-4">{{ $products->links() }}</div> @endif
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4"><h3 class="font-semibold text-gray-900">Recent stock movements</h3><p class="mt-1 text-sm text-gray-500">Latest 30 stock changes across all products.</p></div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[850px] divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-5 py-3.5 font-semibold">Date</th><th class="px-5 py-3.5 font-semibold">Product</th><th class="px-5 py-3.5 font-semibold">Type</th><th class="px-5 py-3.5 text-right font-semibold">Change</th><th class="px-5 py-3.5 text-right font-semibold">Stock</th><th class="px-5 py-3.5 font-semibold">Notes / Reference</th><th class="px-5 py-3.5 font-semibold">By</th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($movements as $movement)
                                <tr><td class="whitespace-nowrap px-5 py-3.5 text-gray-500">{{ $movement->created_at->format('d M Y, H:i') }}</td><td class="px-5 py-3.5 font-medium text-gray-900">{{ $movement->product?->name ?? 'Deleted product' }}@if ($movement->variant)<span class="mt-1 block text-xs text-gray-500">{{ $movement->variant->name }} · {{ $movement->variant->sku }}</span>@endif</td><td class="px-5 py-3.5"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $movement->type === 'in' ? 'bg-green-50 text-green-700' : ($movement->type === 'out' ? 'bg-red-50 text-red-700' : 'bg-blue-50 text-blue-700') }}">{{ str($movement->type)->title() }}</span></td><td class="whitespace-nowrap px-5 py-3.5 text-right font-semibold {{ $movement->stock_after >= $movement->stock_before ? 'text-green-700' : 'text-red-700' }}">{{ $movement->stock_after >= $movement->stock_before ? '+' : '−' }}{{ number_format($movement->quantity) }}</td><td class="whitespace-nowrap px-5 py-3.5 text-right text-gray-700">{{ number_format($movement->stock_before) }} → {{ number_format($movement->stock_after) }}</td><td class="max-w-xs px-5 py-3.5 text-gray-500">{{ $movement->notes ?: ($movement->reference_type ? $movement->reference_type.' #'.$movement->reference_id : '—') }}</td><td class="px-5 py-3.5 text-gray-600">{{ $movement->creator?->name ?? 'System' }}</td></tr>
                            @empty
                                <tr><td colspan="7" class="px-5 py-10 text-center text-sm text-gray-500">No stock movements recorded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
