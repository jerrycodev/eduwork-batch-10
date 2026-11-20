<x-storefront-layout>
    <div class="container">
        <x-ui.page-header eyebrow="Katalog RevStore" title="Jelajahi Produk" description="Temukan produk yang sesuai dengan kebutuhan Anda." />

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[260px_minmax(0,1fr)] lg:gap-8">
            <aside class="rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm shadow-gray-900/[0.025] lg:sticky lg:top-6">
                <div class="mb-5 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-900">Filter Produk</h2>
                    <a href="{{ route('shop') }}" class="text-xs font-semibold text-primary-600 hover:text-primary-700">Reset</a>
                </div>
                <form action="{{ route('shop') }}" method="GET" class="space-y-6">
                    @if ($search)<input type="hidden" name="search" value="{{ $search }}">@endif
                    <section>
                        <h3 class="mb-3 text-sm font-semibold text-gray-900">Kategori</h3>
                        <div class="space-y-2.5">
                            <label class="flex cursor-pointer items-center justify-between gap-2 text-sm text-gray-600">
                                <span class="flex items-center gap-2"><input type="radio" name="category" value="" @checked(!$category) class="border-gray-300 text-primary-600 focus:ring-primary-500"> Semua kategori</span>
                            </label>
                            @foreach ($categories as $item)
                                <label class="flex cursor-pointer items-center justify-between gap-2 rounded-lg p-1.5 text-sm text-gray-600 transition hover:bg-gray-50">
                                    <span class="flex min-w-0 items-center gap-2.5">
                                        <input @change="$el.form.requestSubmit()" type="radio" name="category" value="{{ $item->slug }}" @checked($category === $item->slug) class="border-gray-300 text-primary-600 focus:ring-primary-500">
                                        @if ($item->image)
                                            <img src="{{ Storage::disk('public')->url($item->image) }}" alt="" loading="lazy" class="h-9 w-10 shrink-0 rounded-md object-cover">
                                        @else
                                            <span class="flex h-9 w-10 shrink-0 items-center justify-center rounded-md bg-gray-100 text-gray-400"><i data-lucide="image" class="h-4 w-4"></i></span>
                                        @endif
                                        <span class="truncate">{{ $item->name }}</span>
                                    </span>
                                    <span class="text-xs tabular-nums text-gray-400">{{ $item->products_count }}</span>
                                </label>
                            @endforeach
                        </div>
                    </section>

                    <section class="border-t border-gray-100 pt-5">
                        <h3 class="mb-3 text-sm font-semibold text-gray-900">Rentang Harga</h3>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="text-xs text-gray-500">Minimum
                                <input type="number" name="min_price" min="0" value="{{ request('min_price') }}" placeholder="Rp 0" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500">
                            </label>
                            <label class="text-xs text-gray-500">Maksimum
                                <input type="number" name="max_price" min="0" value="{{ request('max_price') }}" placeholder="Tanpa batas" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500">
                            </label>
                        </div>
                        @if ($priceRange?->min_price !== null)
                            <p class="mt-2 text-xs text-gray-400">Harga tersedia Rp {{ number_format($priceRange->min_price, 0, ',', '.') }} – Rp {{ number_format($priceRange->max_price, 0, ',', '.') }}</p>
                        @endif
                    </section>

                    <section class="border-t border-gray-100 pt-5">
                        <h3 class="mb-3 text-sm font-semibold text-gray-900">Urutkan</h3>
                        <select name="sort" class="w-full rounded-lg border-gray-300 bg-white text-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="latest" @selected($sort === 'latest')>Terbaru</option>
                            <option value="price_asc" @selected($sort === 'price_asc')>Harga terendah</option>
                            <option value="price_desc" @selected($sort === 'price_desc')>Harga tertinggi</option>
                            <option value="rating_desc" @selected($sort === 'rating_desc')>Rating tertinggi</option>
                            <option value="rating_asc" @selected($sort === 'rating_asc')>Rating terendah</option>
                            <option value="name_asc" @selected($sort === 'name_asc')>Nama A–Z</option>
                            <option value="name_desc" @selected($sort === 'name_desc')>Nama Z–A</option>
                        </select>
                    </section>
                    <x-ui.button type="submit" class="w-full"><i data-lucide="sliders-horizontal" class="h-4 w-4"></i>Terapkan Filter</x-ui.button>
                </form>
            </aside>

            <section id="produk" class="min-w-0 scroll-mt-6">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm text-gray-500">Menampilkan <span class="font-semibold text-gray-900">{{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }}</span> dari <span class="font-semibold text-gray-900">{{ $products->total() }}</span> produk</p>
                    @if ($search)<span class="rounded-full bg-primary-50 px-3 py-1 text-xs font-medium text-primary-700">Pencarian: {{ $search }}</span>@endif
                </div>
                <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 xl:grid-cols-3">
                    @forelse ($products as $product)
                        <div class="min-w-0">
                            <x-storefront.product-card :product="$product" />
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
                            <a href="{{ route('shop') }}" class="mt-4 inline-flex text-sm font-semibold text-primary-600 hover:text-primary-700">Hapus semua filter</a>
                        </div>
                    @endforelse
                </div>
                @if ($products->hasPages())<div class="mt-6">{{ $products->links() }}</div>@endif
            </section>
        </div>
    </div>
</x-storefront-layout>
