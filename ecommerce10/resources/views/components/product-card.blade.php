@props(['product', 'image', 'title', 'description' => '', 'link', 'price' => 0])

<article
    class="group flex min-w-0 flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white transition duration-300 hover:-translate-y-1 hover:border-indigo-100 hover:shadow-lg hover:shadow-gray-200/60">
    <a href="{{ $link }}" class="relative block aspect-square overflow-hidden bg-gray-50"
        aria-label="Lihat {{ $title }}">
        <img src="{{ $image }}" alt="{{ $title }}"
            class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
        <span
            class="absolute right-3 top-3 inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/95 text-gray-600 opacity-0 shadow-sm transition group-hover:opacity-100">
            <i data-lucide="arrow-up-right" class="h-4 w-4"></i>
        </span>
    </a>
    <div class="flex flex-1 flex-col p-3 sm:p-4">
        <h3 class="line-clamp-2 text-sm font-semibold leading-5 text-gray-900 sm:text-base"
            title="{{ $title }}">{{ $title }}</h3>
        @if (filled($description))
            <p class="mt-1.5 line-clamp-2 text-xs leading-5 text-gray-500 sm:text-sm">{{ $description }}</p>
        @endif
        <form action="{{ route('cart.store', $product) }}" method="POST"
            class="mt-auto flex flex-col gap-2 pt-4 md:flex-row md:items-center md:justify-between">
            @csrf
            <input type="hidden" name="quantity" value="1">
            <span class="text-sm font-bold tracking-tight text-gray-900 sm:text-base">Rp
                {{ number_format($price, 0, ',', '.') }}</span>
            <button type="submit" @disabled($product->stock < 1)
                class="inline-flex min-h-9 cursor-pointer items-center justify-center gap-1.5 rounded-full bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-600 hover:text-white focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400 disabled:hover:bg-gray-100 sm:text-sm"
                aria-label="{{ $product->stock > 0 ? 'Tambahkan ' . $title . ' ke keranjang' : $title . ' sedang habis' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-shopping-cart-plus preview-icon">
                    <path d="M16 5h6" />
                    <path d="M19 2v6" />
                    <path d="m2.05 2.05 1.099-.028a1 1 0 011.008.815l2.69 14.347A1 1 0 007.83 18H18" />
                    <path d="M4.564 5H12" />
                    <path d="M6.25 14h12.712a2 2 0 001.991-1.57l.172-1.041" />
                    <circle cx="18" cy="20" r="2" />
                    <circle cx="8" cy="20" r="2" />
                </svg>
                <span class="md:hidden lg:inline sm:inline">{{ $product->stock > 0 ? 'Add to Cart' : 'Stok Habis' }}</span>
            </button>
        </form>
    </div>
</article>
