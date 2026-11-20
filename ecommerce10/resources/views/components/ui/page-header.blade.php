@props(['eyebrow' => null, 'title', 'description' => null])
<header {{ $attributes->class(['mb-7 flex flex-wrap items-end justify-between gap-4 sm:mb-8']) }}>
    <div class="min-w-0">
        @if ($eyebrow)<p class="mb-2 text-xs font-semibold uppercase tracking-[0.16em] text-primary-700">{{ $eyebrow }}</p>@endif
        <h1 class="text-2xl font-bold tracking-tight text-gray-950 sm:text-3xl">{{ $title }}</h1>
        @if ($description)<p class="mt-2 max-w-2xl text-sm leading-6 text-gray-500 sm:text-base">{{ $description }}</p>@endif
    </div>
    @if (isset($actions))<div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>@endif
</header>
