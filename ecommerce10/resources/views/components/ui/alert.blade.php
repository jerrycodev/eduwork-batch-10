@props(['type' => 'info', 'icon' => null, 'dismissible' => true])
@php
    $variants = [
        'success' => ['classes' => 'border-emerald-200 bg-emerald-50 text-emerald-800', 'icon' => 'check-circle-2'],
        'error' => ['classes' => 'border-red-200 bg-red-50 text-red-800', 'icon' => 'alert-circle'],
        'warning' => ['classes' => 'border-amber-200 bg-amber-50 text-amber-900', 'icon' => 'alert-triangle'],
        'info' => ['classes' => 'border-primary-200 bg-primary-50 text-primary-900', 'icon' => 'info'],
    ];
    $variant = $variants[$type] ?? $variants['info'];
@endphp
<div x-data="{ visible: true }" x-show="visible" x-transition.opacity {{ $attributes->class(['flex items-start gap-3 rounded-xl border px-4 py-3 text-sm leading-6', $variant['classes']]) }} role="{{ $type === 'error' ? 'alert' : 'status' }}">
    <i data-lucide="{{ $icon ?? $variant['icon'] }}" class="mt-0.5 h-5 w-5 shrink-0"></i>
    <div class="min-w-0 flex-1">{{ $slot }}</div>
    @if ($dismissible)
        <button type="button" aria-label="Tutup notifikasi" @click="visible = false" class="-mr-1 -mt-1 inline-flex size-7 shrink-0 items-center justify-center rounded-lg opacity-60 transition hover:bg-black/5 hover:opacity-100"><i data-lucide="x" class="h-4 w-4"></i></button>
    @endif
</div>
