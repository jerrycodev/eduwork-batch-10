@props(['name', 'show' => false, 'maxWidth' => '2xl'])
@php
    $maxWidth = ['sm' => 'sm:max-w-sm', 'md' => 'sm:max-w-md', 'lg' => 'sm:max-w-lg', 'xl' => 'sm:max-w-xl', '2xl' => 'sm:max-w-2xl'][$maxWidth] ?? 'sm:max-w-2xl';
@endphp
<div x-data="{ show: @js($show), focusables() { return [...$el.querySelectorAll('a, button, input:not([type=hidden]), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])')].filter(el => !el.hasAttribute('disabled')) }, firstFocusable() { return this.focusables()[0] }, lastFocusable() { return this.focusables().slice(-1)[0] }, nextFocusable() { return this.focusables()[(this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1)] || this.firstFocusable() }, prevFocusable() { return this.focusables()[Math.max(0, this.focusables().indexOf(document.activeElement) - 1)] || this.lastFocusable() } }"
    x-init="$watch('show', value => { document.body.classList.toggle('overflow-y-hidden', value); if (value && {{ $attributes->has('focusable') ? 'true' : 'false' }}) setTimeout(() => firstFocusable()?.focus(), 100) })"
    x-on:open-modal.window="$event.detail == '{{ $name }}' ? show = true : null" x-on:close-modal.window="$event.detail == '{{ $name }}' ? show = false : null" x-on:close.stop="show = false" x-on:keydown.escape.window="show = false" x-on:keydown.tab.prevent="$event.shiftKey ? prevFocusable()?.focus() : nextFocusable()?.focus()" x-show="show" x-cloak class="fixed inset-0 z-50 overflow-y-auto px-4 py-8 sm:px-0">
    <div x-show="show" x-transition.opacity class="fixed inset-0 bg-gray-950/40 backdrop-blur-sm" @click="show = false"></div>
    <div x-show="show" x-transition class="relative mx-auto mb-6 w-full overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-2xl {{ $maxWidth }}">
        {{ $slot }}
    </div>
</div>
