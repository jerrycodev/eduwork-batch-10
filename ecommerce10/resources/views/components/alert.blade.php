<div class="alert {{ $class ?? '' }}" role="alert" data-variant="{{ $type ?? '' }}">
    <i class="alert-icon" data-lucide={{ $icon ?? 'info' }}></i>
    <div class="alert-content">
        {{ $slot }}
    </div>
    {{-- Dismiss Button --}}
    <button type="button" class="alert-dismiss" data-action="alert#dismiss" onclick="this.parentElement.remove()">
        <i data-lucide="x" class="h-4 w-4"></i>
    </button>
</div>
