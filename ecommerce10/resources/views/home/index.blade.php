<x-layout-component>

    {{-- List Categories --}}
    @php($activeCategory = request('category'))
    <section class="mt-8 mb-8">
        <div class="mb-4 flex items-end justify-between gap-4">
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
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @foreach ($products as $product)
            <x-product-card :image="$product->image" :title="$product->name" :description="$product->description" :link="route('product-detail', $product)" :price="$product->price" />
        @endforeach
    </div>
    {{ $products->links() }}
</x-layout-component>
