@props(['padding' => 'md'])
@php($paddingClasses = ['none' => '','sm' => 'p-4','md' => 'p-5 sm:p-6','lg' => 'p-6 sm:p-8'])
<section {{ $attributes->class(['rounded-2xl border border-gray-200/80 bg-white shadow-sm shadow-gray-900/[0.025]', $paddingClasses[$padding] ?? $paddingClasses['md']]) }}>
    {{ $slot }}
</section>
