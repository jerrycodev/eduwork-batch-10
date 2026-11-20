@props(['product'])

@php
    $hasOptions = $product->options->isNotEmpty();
    $availableVariants = $product->variants->where('is_active', true);
    $displayPrice = $hasOptions ? $availableVariants->whereNotNull('price')->min('price') : $product->price;
    $hasStock = $hasOptions
        ? $availableVariants->contains(fn ($variant) => (int) $variant->stock > 0)
        : (int) $product->stock > 0;
    $image = $product->main_image ?: 'https://placehold.co/600x600?text=Produk';
@endphp

<article class="group flex min-w-0 flex-col overflow-hidden rounded-xl border border-gray-200 bg-white transition duration-300 hover:-translate-y-1 hover:border-primary-200 hover:shadow-lg hover:shadow-gray-900/5">
    <a href="{{ route('product-detail', $product->slug) }}" class="relative block aspect-square overflow-hidden bg-gray-50" aria-label="Lihat {{ $product->name }}">
        <img src="{{ $image }}" alt="{{ $product->name }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" decoding="async">
        <span aria-hidden="true" class="absolute right-3 top-3 flex h-9 w-9 items-center justify-center rounded-full bg-white/95 text-gray-700 opacity-0 shadow-sm transition group-hover:opacity-100 group-focus-visible:opacity-100">
            <i data-lucide="arrow-up-right" class="h-4 w-4"></i>
        </span>
        @unless ($hasStock)
            <span class="absolute left-3 top-3 rounded-full bg-gray-900/85 px-2.5 py-1 text-[10px] font-semibold text-white">Stok habis</span>
        @endunless
    </a>

    <div class="flex flex-1 flex-col p-3 sm:p-4">
        @if ($product->category)
            <p class="mb-1.5 truncate text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-400">{{ $product->category->name }}</p>
        @endif
        <h3 class="line-clamp-2 min-h-10 text-sm font-semibold leading-5 text-gray-900 sm:text-base" title="{{ $product->name }}">{{ $product->name }}</h3>
        @if (filled($product->description))
            <p class="mt-1.5 line-clamp-2 text-xs leading-5 text-gray-500">{{ $product->description }}</p>
        @endif

        <div class="mt-auto pt-4">
            <p class="mb-3 text-sm font-bold tracking-tight text-gray-900 sm:text-base">
                @if ($hasOptions) <span class="mr-1 text-[10px] font-medium text-gray-500">Mulai dari</span> @endif
                @if ($displayPrice !== null) Rp {{ number_format($displayPrice, 0, ',', '.') }} @else Harga belum tersedia @endif
            </p>
            @if ($hasOptions)
                <a href="{{ route('product-detail', $product->slug) }}" class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-lg bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-700 transition hover:bg-primary-600 hover:text-white sm:text-sm">
                    Pilih varian <i data-lucide="chevron-right" class="h-4 w-4"></i>
                </a>
            @else
                <form action="{{ route('cart.store', ['product' => $product->id]) }}" method="POST">
                    @csrf
                    <input type="hidden" name="quantity" value="1">
                    <x-ui.button type="submit" variant="soft" size="sm" class="w-full" x-bind:disabled="@js(!$hasStock)" aria-label="{{ $hasStock ? 'Tambahkan ' . $product->name . ' ke keranjang' : $product->name . ' sedang habis' }}">
                        <i data-lucide="shopping-cart" class="h-4 w-4"></i>{{ $hasStock ? 'Add to Cart' : 'Stok Habis' }}
                    </x-ui.button>
                </form>
            @endif
        </div>
    </div>
</article>
