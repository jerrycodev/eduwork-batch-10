@props(['links' => []])

<nav aria-label="Breadcrumb" {{ $attributes->class(['mb-6 container']) }}>
    <ol class="flex flex-wrap items-center gap-3 text-sm">
        @foreach ($links as $link)
            @if ($loop->first)
                <li class="flex shrink-0 items-center">
                    <a href="{{ $link[1] ?? route('home') }}"
                        class="inline-flex h-6 w-6 items-center justify-center text-gray-600 transition hover:text-primary-600"
                        aria-label="{{ __('Home') }}">
                        <i data-lucide="house" class="h-5 w-5" aria-hidden="true"></i>
                    </a>
                </li>
            @else
                <li class="flex min-w-0 items-center gap-3">
                    <i data-lucide="chevron-right" class="h-4 w-4 shrink-0 text-gray-300" aria-hidden="true"></i>
                    @if ($loop->last)
                        <span class="truncate font-medium text-primary-600" aria-current="page">
                            {{ $link[0] }}
                        </span>
                    @else
                        <a href="{{ $link[1] }}"
                            class="truncate text-gray-500 transition hover:text-primary-600">
                            {{ $link[0] }}
                        </a>
                    @endif
                </li>
            @endif
        @endforeach
    </ol>
</nav>
