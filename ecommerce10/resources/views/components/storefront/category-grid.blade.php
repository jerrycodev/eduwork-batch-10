@props([
    'categories' => collect(),
    'activeCategory' => null,
    'title' => 'Kategori Pilihan',
    'description' => 'Jelajahi koleksi pilihan kami dan temukan produk yang sesuai dengan gaya Anda.',
    'showCount' => true,
])

@php
    $activeCategory = $activeCategory ?? request('category');
    $categoryCount = $categories->count();
@endphp

<section id="kategori" {{ $attributes->class(['scroll-mt-28 py-2']) }} aria-labelledby="categories-title">
    <div class="mb-6 flex flex-col gap-3 sm:mb-8 sm:flex-row sm:items-end sm:justify-between">
        <div class="max-w-2xl">
            <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-primary-600">Temukan berdasarkan kategori</p>
            <h2 id="categories-title" class="text-2xl font-bold tracking-tight text-gray-950 sm:text-3xl">{{ $title }}</h2>
            <p class="mt-2 text-sm leading-6 text-gray-500 sm:text-base">{{ $description }}</p>
        </div>
        @if ($showCount)
            <p class="shrink-0 text-sm text-gray-500"><span class="font-semibold text-gray-900">{{ $categoryCount }}</span> kategori tersedia</p>
        @endif
    </div>

    @if ($categories->isNotEmpty())
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4 xl:grid-cols-5">
            @foreach ($categories as $category)
                @php($isActive = $activeCategory === $category->slug)
                <a href="{{ route('shop', ['category' => $category->slug]) }}"
                    @if ($isActive) aria-current="true" @endif
                    @class([
                        'group relative isolate block overflow-hidden rounded-xl bg-gray-100 ring-offset-2 transition duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500',
                        'ring-2 ring-primary-500' => $isActive,
                        'ring-1 ring-gray-200 hover:-translate-y-1 hover:shadow-lg hover:shadow-gray-900/10' => !$isActive,
                    ])>
                    <div class="relative aspect-[5/4] overflow-hidden sm:aspect-[4/5]">
                        @if ($category->image)
                            <img src="{{ Storage::disk('public')->url($category->image) }}" alt="{{ $category->name }}" loading="lazy" decoding="async" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                        @else
                            <div class="flex h-full items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200 text-gray-400"><i data-lucide="image" class="h-8 w-8"></i></div>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-gray-950/80 via-gray-950/10 to-transparent"></div>
                        <div class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-2 p-3 sm:p-4">
                            <div class="min-w-0">
                                <h3 class="truncate text-sm font-semibold text-white sm:text-base">{{ $category->name }}</h3>
                                <p class="mt-1 text-[11px] text-white/75 sm:text-xs">{{ $category->products_count ?? 0 }} produk</p>
                            </div>
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-white/40 bg-white/15 text-white backdrop-blur transition group-hover:bg-white group-hover:text-gray-900"><i data-lucide="arrow-up-right" class="h-4 w-4"></i></span>
                        </div>
                        @if ($isActive)
                            <span class="absolute left-3 top-3 rounded-full bg-white px-2.5 py-1 text-[10px] font-semibold text-primary-700">Dipilih</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-5 py-8 text-center text-sm text-gray-500">Kategori akan segera tersedia.</div>
    @endif
</section>
