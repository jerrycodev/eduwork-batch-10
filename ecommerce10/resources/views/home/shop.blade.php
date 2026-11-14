<x-layout-component>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Jelajahi Produk</h1>
            <p class="mt-1 text-sm text-gray-500">Temukan produk yang sesuai dengan kebutuhan Anda.</p>
        </div>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[260px_minmax(0,1fr)] lg:gap-8">
            <aside class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm lg:sticky lg:top-6">
                <div class="mb-5 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-900">Filter Produk</h2>
                    <a href="{{ route('shop') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Reset</a>
                </div>
                <form action="{{ route('shop') }}" method="GET" class="space-y-6">
                    @if ($search)<input type="hidden" name="search" value="{{ $search }}">@endif
                    <section>
                        <h3 class="mb-3 text-sm font-semibold text-gray-900">Kategori</h3>
                        <div class="space-y-2.5">
                            <label class="flex cursor-pointer items-center justify-between gap-2 text-sm text-gray-600">
                                <span class="flex items-center gap-2"><input type="radio" name="category" value="" @checked(!$category) class="border-gray-300 text-indigo-600 focus:ring-indigo-500"> Semua kategori</span>
                            </label>
                            @foreach ($categories as $item)
                                <label class="flex cursor-pointer items-center justify-between gap-2 text-sm text-gray-600">
                                    <span class="flex min-w-0 items-center gap-2"><input type="radio" name="category" value="{{ $item->slug }}" @checked($category === $item->slug) class="border-gray-300 text-indigo-600 focus:ring-indigo-500"><span class="truncate">{{ $item->name }}</span></span>
                                    <span class="text-xs tabular-nums text-gray-400">{{ $item->products_count }}</span>
                                </label>
                            @endforeach
                        </div>
                    </section>

                    <section class="border-t border-gray-100 pt-5">
                        <h3 class="mb-3 text-sm font-semibold text-gray-900">Rentang Harga</h3>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="text-xs text-gray-500">Minimum
                                <input type="number" name="min_price" min="0" value="{{ request('min_price') }}" placeholder="Rp 0" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </label>
                            <label class="text-xs text-gray-500">Maksimum
                                <input type="number" name="max_price" min="0" value="{{ request('max_price') }}" placeholder="Tanpa batas" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </label>
                        </div>
                        @if ($priceRange?->min_price !== null)
                            <p class="mt-2 text-xs text-gray-400">Harga tersedia Rp {{ number_format($priceRange->min_price, 0, ',', '.') }} – Rp {{ number_format($priceRange->max_price, 0, ',', '.') }}</p>
                        @endif
                    </section>

                    <section class="border-t border-gray-100 pt-5">
                        <h3 class="mb-3 text-sm font-semibold text-gray-900">Urutkan</h3>
                        <select name="sort" class="w-full rounded-lg border-gray-300 bg-white text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="latest" @selected($sort === 'latest')>Terbaru</option>
                            <option value="price_asc" @selected($sort === 'price_asc')>Harga terendah</option>
                            <option value="price_desc" @selected($sort === 'price_desc')>Harga tertinggi</option>
                            <option value="rating_desc" @selected($sort === 'rating_desc')>Rating tertinggi</option>
                            <option value="rating_asc" @selected($sort === 'rating_asc')>Rating terendah</option>
                            <option value="name_asc" @selected($sort === 'name_asc')>Nama A–Z</option>
                            <option value="name_desc" @selected($sort === 'name_desc')>Nama Z–A</option>
                        </select>
                    </section>
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700"><i data-lucide="sliders-horizontal" class="h-4 w-4"></i>Terapkan Filter</button>
                </form>
            </aside>

            <section id="produk" class="min-w-0 scroll-mt-6">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm text-gray-500">Menampilkan <span class="font-semibold text-gray-900">{{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }}</span> dari <span class="font-semibold text-gray-900">{{ $products->total() }}</span> produk</p>
                    @if ($search)<span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700">Pencarian: {{ $search }}</span>@endif
                </div>
                <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 xl:grid-cols-3">
                    @forelse ($products as $product)
                        <div class="min-w-0">
                            <x-product-card :product="$product" :image="($product->image ?: 'https://placehold.co/600x600?text=No+Image')" :title="$product->name" :description="$product->description" :link="route('product-detail', $product)" :price="$product->price" />
                            <div class="px-1 pt-2 text-xs text-gray-500">
                                @if ($product->reviews_count > 0)
                                    <span class="inline-flex items-center gap-1 font-medium text-amber-600"><i data-lucide="star" class="h-3.5 w-3.5 fill-current"></i>{{ number_format($product->reviews_avg_rating, 1) }}</span>
                                    <span>({{ $product->reviews_count }} ulasan)</span>
                                @else
                                    <span>Belum ada ulasan</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full rounded-xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center">
                            <i data-lucide="package-search" class="mx-auto h-9 w-9 text-gray-300"></i>
                            <h3 class="mt-3 font-semibold text-gray-900">Produk tidak ditemukan</h3>
                            <p class="mt-1 text-sm text-gray-500">Coba ubah kategori atau rentang harga yang dipilih.</p>
                            <a href="{{ route('shop') }}" class="mt-4 inline-flex text-sm font-semibold text-indigo-600 hover:text-indigo-700">Hapus semua filter</a>
                        </div>
                    @endforelse
                </div>
                @if ($products->hasPages())<div class="mt-6">{{ $products->links() }}</div>@endif
            </section>
        </div>
    </div>
</x-layout-component>
