@props(['active'])
@php
    $classes = ($active ?? false)
        ? 'inline-flex h-full items-center border-b-2 border-indigo-600 px-1 pt-1 text-sm font-semibold text-indigo-700 transition'
        : 'inline-flex h-full items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 transition hover:border-indigo-200 hover:text-indigo-700';
@endphp
<a {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
