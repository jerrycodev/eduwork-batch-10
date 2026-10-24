<div class="alert" role="alert">
    <i class="alert-icon" data-lucide={{$icon ?? 'info'}}></i>
    <div class="alert-content">
        @if (isset($title))
            <h5 class="alert-title">{{ $title }}</h5>
        @endif
        @if (isset($description))
            <p class="alert-description">{{ $description }}</p>
        @endif
    </div>
</div>
