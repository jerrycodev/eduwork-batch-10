@props(['type' => 'info', 'icon' => null])
@php
    $variants = [
        'success' => ['classes' => 'border-emerald-200 bg-emerald-50 text-emerald-800', 'icon' => 'check-circle-2'],
        'error' => ['classes' => 'border-red-200 bg-red-50 text-red-800', 'icon' => 'alert-circle'],
        'warning' => ['classes' => 'border-amber-200 bg-amber-50 text-amber-800', 'icon' => 'alert-triangle'],
        'info' => ['classes' => 'border-indigo-200 bg-indigo-50 text-indigo-800', 'icon' => 'info'],
    ];
    $variant = $variants[$type] ?? $variants['info'];
@endphp
<div {{ $attributes->class(['flex items-start gap-3 rounded-xl border px-4 py-3 text-sm leading-6', $variant['classes']]) }} role="alert">
    <i data-lucide="{{ $icon ?? $variant['icon'] }}" class="mt-0.5 h-5 w-5 shrink-0"></i>
    <div class="min-w-0 flex-1">{{ $slot }}</div>
    <button type="button" aria-label="Tutup notifikasi" onclick="this.closest('[role=alert]').remove()" class="-mr-1 -mt-1 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg opacity-60 transition hover:bg-black/5 hover:opacity-100">
        <i data-lucide="x" class="h-4 w-4"></i>
    </button>
</div>
