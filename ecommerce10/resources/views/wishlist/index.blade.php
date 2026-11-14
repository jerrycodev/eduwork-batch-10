<x-layout-component>
    <div class="mb-8 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Wishlist Saya</h1>
            <p class="mt-1 text-sm text-gray-500">Produk yang Anda simpan untuk dibeli nanti.</p>
        </div>
        <a href="{{ route('shop') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">Jelajahi produk</a>
    </div>

    @if (session('success'))
        <div class="mb-5 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    @if ($wishlistItems->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-300 px-6 py-16 text-center">
            <i data-lucide="heart" class="mx-auto h-10 w-10 text-gray-300"></i>
            <h2 class="mt-4 font-semibold text-gray-900">Wishlist masih kosong</h2>
            <p class="mt-1 text-sm text-gray-500">Simpan produk yang Anda sukai agar mudah ditemukan kembali.</p>
            <a href="{{ route('shop') }}" class="mt-5 inline-flex rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Lihat Produk</a>
        </div>
    @else
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($wishlistItems as $item)
                <article class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <a href="{{ route('product-detail', $item->product) }}" class="block">
                        <div class="aspect-square overflow-hidden bg-gray-50">
                            <img src="{{ $item->product->image ?: 'https://placehold.co/600x600' }}" alt="{{ $item->product->name }}" loading="lazy" class="h-full w-full object-cover">
                        </div>
                        <div class="p-3 sm:p-4">
                            <h2 class="line-clamp-2 min-h-10 text-sm font-semibold text-gray-900">{{ $item->product->name }}</h2>
                            <p class="mt-2 text-sm font-bold text-gray-900">Rp {{ number_format($item->product->price, 0, ',', '.') }}</p>
                            <p class="mt-1 text-xs {{ $item->product->stock > 0 ? 'text-green-600' : 'text-rose-600' }}">{{ $item->product->stock > 0 ? 'Stok tersedia' : 'Stok habis' }}</p>
                        </div>
                    </a>
                    <div class="border-t border-gray-100 p-3">
                        <form action="{{ route('wishlist.destroy', $item) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600">Hapus dari Wishlist</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</x-layout-component>
