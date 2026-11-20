@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
])
@php
    $base = 'ui-button inline-flex shrink-0 items-center justify-center gap-2 font-semibold transition duration-200 ease-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 active:translate-y-px';
    $variants = [
        'primary' => 'bg-primary-600 text-white shadow-sm shadow-primary-900/10 hover:bg-primary-700 focus-visible:ring-primary-500',
        'secondary' => 'border border-gray-200 bg-white text-gray-700 shadow-sm hover:border-primary-200 hover:bg-primary-50 hover:text-primary-800 focus-visible:ring-primary-500',
        'soft' => 'bg-primary-50 text-primary-800 hover:bg-primary-100 focus-visible:ring-primary-500',
        'outline' => 'border border-gray-200 bg-transparent text-gray-700 hover:border-gray-300 hover:bg-gray-50 focus-visible:ring-primary-500',
        'danger' => 'bg-red-600 text-white shadow-sm hover:bg-red-700 focus-visible:ring-red-500',
        'danger-soft' => 'border border-red-200 bg-white text-red-700 hover:bg-red-50 focus-visible:ring-red-500',
        'ghost' => 'bg-transparent text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus-visible:ring-primary-500',
    ];
    $sizes = [
        'xs' => 'min-h-8 rounded-lg px-3 py-1.5 text-xs',
        'sm' => 'min-h-9 rounded-lg px-3.5 py-2 text-xs sm:text-sm',
        'md' => 'min-h-11 rounded-xl px-4 py-2.5 text-sm',
        'lg' => 'min-h-12 rounded-xl px-5 py-3 text-sm',
        'icon' => 'size-10 rounded-xl p-0',
    ];
@endphp
<button type="{{ $type }}" {{ $attributes->class([$base, $variants[$variant] ?? $variants['primary'], $sizes[$size] ?? $sizes['md']]) }}>
    {{ $slot }}
</button>
