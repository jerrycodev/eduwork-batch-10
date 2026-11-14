@props(['active'])
@php
    $classes = ($active ?? false)
        ? 'block w-full rounded-xl bg-indigo-50 px-4 py-2.5 text-start text-sm font-semibold text-indigo-700 transition'
        : 'block w-full rounded-xl px-4 py-2.5 text-start text-sm font-medium text-gray-600 transition hover:bg-gray-50 hover:text-indigo-700';
@endphp
<a {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
