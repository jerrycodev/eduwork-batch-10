@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}"
        class="flex flex-col items-center justify-between gap-4 sm:flex-row">
        <p class="text-sm text-gray-600">
            {!! __('Showing') !!}
            @if ($paginator->firstItem())
                <span class="font-semibold text-gray-900">{{ $paginator->firstItem() }}</span>
                {!! __('to') !!}
                <span class="font-semibold text-gray-900">{{ $paginator->lastItem() }}</span>
            @else
                <span class="font-semibold text-gray-900">{{ $paginator->count() }}</span>
            @endif
            {!! __('of') !!}
            <span class="font-semibold text-gray-900">{{ $paginator->total() }}</span>
            {!! __('results') !!}
        </p>

        <div class="inline-flex items-center gap-1" aria-label="{{ __('Pagination') }}">
            {{-- Previous page --}}
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}"
                    class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-md text-gray-300">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                    aria-label="{{ __('pagination.previous') }}"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-md text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
            @endif

            {{-- Page numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span aria-disabled="true"
                        class="inline-flex h-9 min-w-9 items-center justify-center px-2 text-sm font-medium text-gray-400">
                        {{ $element }}
                    </span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page"
                                class="inline-flex h-9 min-w-9 items-center justify-center rounded-md bg-indigo-600 px-3 text-sm font-semibold text-white shadow-sm">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}"
                                aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                                class="inline-flex h-9 min-w-9 items-center justify-center rounded-md px-3 text-sm font-medium text-gray-700 transition hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next page --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                    aria-label="{{ __('pagination.next') }}"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-md text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            @else
                <span aria-disabled="true" aria-label="{{ __('pagination.next') }}"
                    class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-md text-gray-300">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
