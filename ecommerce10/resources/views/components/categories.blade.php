@props([
    'categories' => collect(),
    'activeCategory' => null,
    'title' => 'Kategori',
    'description' => 'Temukan produk berdasarkan kategori favoritmu.',
    'showCount' => true,
])

@php
    $activeCategory = $activeCategory ?? request('category');
    $categoryCount = $categories->count();
@endphp

<section id="kategori" {{ $attributes->class(['my-8 scroll-mt-24 sm:my-10']) }}>
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div class="space-y-1">
            <h2 class="text-xl font-semibold tracking-tight text-gray-900 sm:text-2xl">{{ $title }}</h2>
            <p class="text-sm leading-6 text-gray-500">{{ $description }}</p>
        </div>
        @if ($showCount)
            <span class="inline-flex w-fit items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-100">
                {{ $categoryCount }} kategori
            </span>
        @endif
    </div>

    <nav aria-label="Filter kategori" class="flex flex-wrap gap-2">
        <a href="{{ route('shop') }}" @class([
            'inline-flex min-h-9 items-center rounded-full border px-3 py-1.5 text-xs font-medium sm:min-h-10 sm:px-4 sm:py-2 sm:text-sm transition duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2',
            'border-indigo-600 bg-indigo-600 text-white shadow-sm shadow-indigo-600/15 hover:border-indigo-700 hover:bg-indigo-700' => !$activeCategory,
            'border-gray-200 bg-white text-gray-600 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700' => $activeCategory,
        ]) @if (!$activeCategory) aria-current="true" @endif>
            Semua Produk
        </a>

        @foreach ($categories as $category)
            @php($isActive = $activeCategory === $category->slug)
            <a href="{{ route('shop', ['category' => $category->slug]) }}" @class([
                'group inline-flex min-h-9 items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-medium sm:min-h-10 sm:gap-2 sm:px-4 sm:py-2 sm:text-sm transition duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2',
                'border-indigo-600 bg-indigo-600 text-white shadow-sm shadow-indigo-600/15 hover:border-indigo-700 hover:bg-indigo-700' => $isActive,
                'border-gray-200 bg-white text-gray-600 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700' => !$isActive,
            ]) @if ($isActive) aria-current="true" @endif>
                <span>{{ $category->name }}</span>
                <span @class([
                    'inline-flex min-w-6 items-center justify-center rounded-full px-2 py-0.5 text-xs font-semibold tabular-nums transition-colors',
                    'bg-white/15 text-white' => $isActive,
                    'bg-gray-100 text-gray-500 group-hover:bg-indigo-100 group-hover:text-indigo-700' => !$isActive,
                ])>{{ $category->products_count ?? 0 }}</span>
            </a>
        @endforeach
    </nav>
</section>
