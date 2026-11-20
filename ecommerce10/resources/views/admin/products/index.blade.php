{{-- Dokumentasi: Daftar katalog produk dengan filter, pagination, dan aksi pengelolaan. --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold tracking-tight text-gray-900">Products</h2>
                <p class="mt-1 text-sm text-gray-500">Manage your product catalog and inventory.</p>
            </div>
            <a href="{{ route('products.create') }}"
                class="bg-primary-600 hover:bg-primary-700 focus:ring-primary-200 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition focus:outline-none focus:ring-4"><i
                    data-lucide="plus" class="h-4 w-4"></i>Create Product</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="flex w-full flex-col gap-5 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <x-alert class="border border-green-200 bg-green-50 text-green-800">{{ session('success') }}</x-alert>
            @endif
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h3 class="font-semibold text-gray-900">Filter products</h3>
                    <p class="mt-0.5 text-sm text-gray-500">Search by product name or SKU, then narrow the list by
                        category or status.</p>
                </div>
                <form action="{{ route('products.index') }}" method="GET"
                    class="p-5">
                    <div
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(220px,1fr)_220px_220px_auto_auto]">
                        <label class="relative block"><span class="sr-only">Search products</span><i
                                data-lucide="search"
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i><input
                                type="search" name="search" placeholder="Search products..."
                                value="{{ request('search') }}"
                                class="focus:border-primary-500 focus:ring-primary-100 w-full rounded-lg border border-gray-300 py-2.5 pl-9 pr-3 text-sm outline-none transition placeholder:text-gray-400 focus:ring-4"></label>
                        <select name="category_id"
                            class="focus:border-primary-500 focus:ring-primary-100 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-700 outline-none focus:ring-4">
                            <option value="">All categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        <select name="order"
                            class="focus:border-primary-500 focus:ring-primary-100 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-700 outline-none focus:ring-4">
                            <option value="">Sort by</option>
                            <option value="name" @selected(request('order') === 'name')>Name A–Z</option>
                            <option value="name_desc" @selected(request('order') === 'name_desc')>Name Z–A</option>
                            <option value="price" @selected(request('order') === 'price')>Price: low to high</option>
                            <option value="price_desc" @selected(request('order') === 'price_desc')>Price: high to low</option>
                            <option value="stock" @selected(request('order') === 'stock')>Stock: low to high</option>
                            <option value="stock_desc" @selected(request('order') === 'stock_desc')>Stock: high to low</option>
                        </select>
                        <button type="submit"
                            class="bg-primary-600 hover:bg-primary-700 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition"><i
                                data-lucide="filter" class="h-4 w-4"></i>Filter</button>
                        @if (request()->hasAny(['search', 'category_id', 'order']))
                            <a href="{{ route('products.index') }}"
                                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"><i
                                    data-lucide="x" class="h-4 w-4"></i>Clear</a>
                        @endif
                    </div>
                </form>
            </section>


            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h3 class="font-semibold text-gray-900">Product list</h3>
                        <p class="mt-0.5 text-sm text-gray-500">{{ $products->total() }} products found</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-5 py-3.5 font-semibold">Product</th>
                                <th class="px-5 py-3.5 font-semibold">Price</th>
                                <th class="px-5 py-3.5 font-semibold">Stock</th>
                                <th class="px-5 py-3.5 font-semibold">Category</th>
                                <th class="px-5 py-3.5 font-semibold">Description</th>
                                <th class="px-5 py-3.5 text-right font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($products as $product)
                                <tr class="transition hover:bg-gray-50/80">
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center gap-3"><img
                                                src="{{ $product->image ?: 'https://placehold.co/80x80?text=No+Image' }}"
                                                alt="{{ $product->name }}"
                                                class="h-11 w-11 rounded-lg border border-gray-100 object-cover">
                                            <div class="min-w-0">
                                                <p class="font-semibold text-gray-900">{{ $product->name }}</p>
                                                <p class="mt-0.5 text-xs text-gray-500">{{ $product->slug }}</p>
                                                <p class="mt-0.5 text-xs text-gray-400">SKU: {{ $product->sku ?: '—' }}</p>
                                                <div class="mt-1 flex flex-wrap gap-1">
                                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $product->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $product->is_active ? 'Active' : 'Inactive' }}</span>
                                                    @if ($product->is_featured)
                                                        <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Featured</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3.5 font-medium text-gray-900">Rp
                                        {{ number_format($product->price, 0, ',', '.') }}</td>
                                    <td class="px-5 py-3.5"><span
                                            class="{{ $product->stock > 0 ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }} inline-flex rounded-full px-2.5 py-1 text-xs font-semibold">{{ $product->stock }}
                                            in stock</span></td>
                                    <td class="px-5 py-3.5 text-gray-600">{{ $product->category?->name ?? '—' }}</td>
                                    <td class="max-w-xs px-5 py-3.5">
                                        <p class="truncate text-gray-500" title="{{ $product->description }}">
                                            {{ $product->description }}</p>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center justify-end gap-1"><a
                                                href="{{ route('products.show', $product) }}"
                                                class="hover:bg-primary-50 hover:text-primary-600 rounded-md p-2 text-gray-500 transition"
                                                aria-label="View {{ $product->name }}" title="View"><i
                                                    data-lucide="eye" class="h-4 w-4"></i></a><a
                                                href="{{ route('products.edit', $product) }}"
                                                class="hover:bg-primary-50 hover:text-primary-600 rounded-md p-2 text-gray-500 transition"
                                                aria-label="Edit {{ $product->name }}" title="Edit"><i
                                                    data-lucide="pencil" class="h-4 w-4"></i></a>
                                            <form action="{{ route('products.destroy', $product) }}" method="POST"
                                                x-data="adminConfirm(@js('Delete this product?'))" @submit="submit($event)">@csrf
                                                @method('DELETE')<button type="submit"
                                                    class="rounded-md p-2 text-gray-500 transition hover:bg-red-50 hover:text-red-600"
                                                    aria-label="Delete {{ $product->name }}" title="Delete"><i
                                                        data-lucide="trash-2" class="h-4 w-4"></i></button></form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-16 text-center">
                                        <div class="mx-auto flex max-w-sm flex-col items-center"><span
                                                class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400"><i
                                                    data-lucide="package-search" class="h-6 w-6"></i></span>
                                            <p class="mt-3 font-semibold text-gray-900">No products found</p>
                                            <p class="mt-1 text-sm text-gray-500">Try adjusting your filters or add a
                                                new product.</p><a href="{{ route('products.create') }}"
                                                class="text-primary-600 hover:text-primary-700 mt-4 text-sm font-semibold">Create
                                                your first product</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($products->hasPages())
                    <div class="border-t border-gray-100 px-5 py-4">
                        {{ $products->appends(request()->query())->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
