<x-layout-component>

    {{-- Hero Section --}}
    <section class="relative isolate mb-8 overflow-hidden rounded-2xl border border-[var(--border)] bg-[var(--card)] sm:mb-10 sm:rounded-3xl">
        <div class="absolute inset-0 -z-10 bg-gradient-to-br from-[var(--muted)] via-[var(--card)] to-[var(--background)]"></div>
        <div class="absolute -right-16 -top-20 -z-10 h-64 w-64 rounded-full bg-[var(--primary)] opacity-[0.07] blur-3xl sm:h-80 sm:w-80"></div>
        <div class="absolute -bottom-24 right-1/3 -z-10 h-48 w-48 rounded-full bg-[var(--primary)] opacity-[0.05] blur-3xl"></div>

        <div class="grid items-center gap-8 px-6 py-10 sm:px-10 sm:py-14 lg:grid-cols-[minmax(0,1fr)_auto] lg:gap-12 lg:px-14 lg:py-16">
            <div class="max-w-2xl">
                <span class="mb-4 inline-flex items-center gap-2 rounded-full border border-[var(--border)] bg-[var(--background)]/80 px-3 py-1.5 text-xs font-semibold text-[var(--muted-foreground)]">
                    <span class="h-2 w-2 rounded-full bg-[var(--primary)]"></span>
                    Pilihan tepat untuk kebutuhan sehari-hari
                </span>
                <h1 class="max-w-xl text-3xl font-bold tracking-tight text-[var(--foreground)] sm:text-4xl lg:text-5xl">
                    Temukan produk favoritmu, <span class="text-[var(--primary)]">lebih mudah.</span>
                </h1>
                <p class="mt-4 max-w-xl text-sm leading-6 text-[var(--muted-foreground)] sm:text-base sm:leading-7">
                    Jelajahi berbagai pilihan elektronik dan kebutuhan harian dengan pengalaman belanja yang praktis.
                    Temukan produk yang cocok untukmu.
                </p>
                <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                    <a href="#produk" class="btn inline-flex min-h-11 items-center justify-center gap-2 px-5" data-variant="default">
                        Jelajahi Produk
                        <i data-lucide="arrow-right" class="h-4 w-4"></i>
                    </a>
                    <a href="#kategori" class="btn inline-flex min-h-11 items-center justify-center px-5" data-variant="outline">
                        Lihat Kategori
                    </a>
                </div>
                <div class="mt-8 flex flex-wrap items-center gap-x-6 gap-y-3 border-t border-[var(--border)] pt-5 text-xs text-[var(--muted-foreground)] sm:text-sm">
                    <span class="inline-flex items-center gap-2"><i data-lucide="shopping-bag" class="h-4 w-4"></i> Pilihan produk beragam</span>
                    <span class="inline-flex items-center gap-2"><i data-lucide="layers-3" class="h-4 w-4"></i> {{ $categories->count() }} kategori</span>
                </div>
            </div>

            <div class="hidden lg:flex lg:items-center lg:justify-center">
                <div class="relative flex h-52 w-52 items-center justify-center rounded-full border border-[var(--border)] bg-[var(--background)]/70 shadow-sm xl:h-64 xl:w-64">
                    <div class="absolute inset-5 rounded-full border border-dashed border-[var(--border)]"></div>
                    <div class="flex h-28 w-28 items-center justify-center rounded-3xl bg-[var(--primary)] text-[var(--primary-foreground)] shadow-xl xl:h-36 xl:w-36">
                        <i data-lucide="shopping-bag" class="h-14 w-14 xl:h-16 xl:w-16"></i>
                    </div>
                    <span class="absolute -right-3 top-8 rounded-full border border-[var(--border)] bg-[var(--card)] px-3 py-2 text-xs font-semibold text-[var(--foreground)] shadow-sm">Shop easy</span>
                    <span class="absolute -bottom-2 left-0 rounded-full border border-[var(--border)] bg-[var(--card)] px-3 py-2 text-xs font-semibold text-[var(--foreground)] shadow-sm">Find your pick</span>
                </div>
            </div>
        </div>
    </section>

    {{-- List Categories --}}
    @php($activeCategory = request('category'))
    <section id="kategori" class="my-6 scroll-mt-6 sm:my-8">
        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between sm:gap-4">
            <div>
                <h2 class="text-lg font-semibold tracking-tight text-[var(--foreground)]">Kategori</h2>
                <p class="text-sm text-[var(--muted-foreground)]">Temukan produk berdasarkan kategori favoritmu.
                </p>
            </div>
            <span
                class="hidden shrink-0 rounded-full bg-[var(--muted)] px-3 py-1 text-xs font-medium text-[var(--muted-foreground)] sm:inline-flex">
                {{ $categories->count() }} kategori
            </span>
        </div>

        <div class="-mx-4 flex snap-x gap-2 overflow-x-auto px-4 pb-2 sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-0">
            <a href="{{ route('shop') }}" @class([
                'inline-flex shrink-0 snap-start items-center gap-2 rounded-full border px-4 py-2 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-[var(--ring)] focus-visible:ring-offset-2 focus-visible:outline-none',
                'border-[var(--primary)] bg-[var(--primary)] text-[var(--primary-foreground)]' => !$activeCategory,
                'border-[var(--border)] bg-[var(--card)] text-[var(--foreground)] hover:bg-[var(--accent)]' => $activeCategory,
            ])>
                Semua Produk
            </a>

            @foreach ($categories as $category)
                @php($isActive = $activeCategory === $category->slug)
                <a href="{{ route('shop', ['category' => $category->slug]) }}" @class([
                    'group inline-flex shrink-0 snap-start items-center gap-2 rounded-full border px-4 py-2 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-[var(--ring)] focus-visible:ring-offset-2 focus-visible:outline-none',
                    'border-[var(--primary)] bg-[var(--primary)] text-[var(--primary-foreground)]' => $isActive,
                    'border-[var(--border)] bg-[var(--card)] text-[var(--foreground)] hover:bg-[var(--accent)]' => !$isActive,
                ])">
                    <span>{{ $category->name }}</span>
                    <span @class([
                        'inline-flex min-w-6 items-center justify-center rounded-full px-2 py-0.5 text-xs font-semibold transition-colors',
                        'bg-black/10 text-[var(--primary-foreground)]' => $isActive,
                        'bg-[var(--muted)] text-[var(--muted-foreground)] group-hover:bg-[var(--background)]' => !$isActive,
                    ])>
                        {{ $category->products_count }}
                    </span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- List Products --}}
    <div id="produk" class="grid scroll-mt-6 grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 xl:grid-cols-4">
        @foreach ($products as $product)
            <x-product-card :image="$product->image" :title="$product->name" :description="$product->description" :link="route('product-detail', $product)" :price="$product->price" />
        @endforeach
    </div>
    <div class="py-5 sm:py-6">
        {{ $products->links() }}
    </div>
</x-layout-component>
