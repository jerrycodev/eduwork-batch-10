@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1'])
@php
    $alignmentClasses = match ($align) {
        'left' => 'ltr:origin-top-left rtl:origin-top-right start-0',
        'top' => 'origin-top',
        default => 'ltr:origin-top-right rtl:origin-top-left end-0',
    };
    $width = match ($width) { '48' => 'w-48', default => $width };
@endphp
<div class="relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
    <div @click="open = !open">{{ $trigger }}</div>
    <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="scale-100 opacity-100" x-transition:leave-end="scale-95 opacity-0" class="absolute z-50 mt-2 {{ $width }} {{ $alignmentClasses }}" style="display:none" @click="open = false">
        <div class="rounded-2xl border border-gray-100 bg-white p-1.5 shadow-xl shadow-gray-900/10 {{ $contentClasses }}">{{ $content }}</div>
    </div>
</div>
