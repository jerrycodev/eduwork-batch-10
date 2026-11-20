<x-storefront-layout>
    <x-slot:title>Beranda | {{ config('app.name', 'RevStore') }}</x-slot:title>
    <x-slot:description>Temukan produk pilihan dan koleksi menarik di {{ config('app.name', 'RevStore') }}. Jelajahi kategori dan belanja dengan mudah.</x-slot:description>

    <x-storefront.hero />

    <div class="container">
        <x-storefront.category-grid
            :categories="$categories"
            title="Jelajahi Kategori"
            description="Temukan produk berdasarkan kategori yang paling sesuai dengan kebutuhan Anda."
            class="my-12 sm:my-16"
        />

        <section id="produk" class="scroll-mt-28 pb-14 sm:pb-20" aria-labelledby="home-products-title">
            <div class="mb-6 flex flex-wrap items-end justify-between gap-4 sm:mb-8">
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-primary-600">Dikurasi untuk Anda</p>
                    <h2 id="home-products-title" class="text-2xl font-bold tracking-tight text-gray-950 sm:text-3xl">Produk Pilihan</h2>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-gray-500">Lihat koleksi produk yang tersedia dan temukan pilihan favorit Anda.</p>
                </div>
                <a href="{{ route('shop') }}" class="group inline-flex items-center gap-2 text-sm font-semibold text-primary-700 transition hover:text-primary-800">
                    Lihat semua produk
                    <i data-lucide="arrow-up-right" class="h-4 w-4 transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5"></i>
                </a>
            </div>

            @if ($products->isNotEmpty())
                <div class="grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-3 xl:grid-cols-4">
                    @foreach ($products as $product)
                        <x-storefront.product-card :product="$product" />
                    @endforeach
                </div>

                @if ($products->hasPages())
                    <nav class="mt-8 flex justify-center" aria-label="Navigasi halaman produk">
                        {{ $products->onEachSide(1)->links() }}
                    </nav>
                @endif
            @else
                <div class="rounded-2xl border border-dashed border-gray-300 bg-gray-50/70 px-6 py-14 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-white text-gray-400 ring-1 ring-gray-200">
                        <i data-lucide="package-search" class="h-6 w-6"></i>
                    </span>
                    <h3 class="mt-4 text-base font-semibold text-gray-900">Belum ada produk tersedia</h3>
                    <p class="mt-1 text-sm text-gray-500">Silakan kembali lagi nanti untuk melihat koleksi terbaru.</p>
                </div>
            @endif
        </section>
    </div>
</x-storefront-layout>
