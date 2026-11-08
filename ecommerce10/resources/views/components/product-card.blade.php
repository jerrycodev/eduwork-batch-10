<div class="card group flex min-w-0 flex-col overflow-hidden">
    
    <!-- Image Section dengan efek zoom saat di-hover -->
    <div class="relative aspect-square overflow-hidden bg-muted/50">
        <img 
            src="{{ $image }}" 
            alt="{{ $title }}" 
            class="object-cover w-full h-full transition-transform duration-300 group-hover:scale-105"
            loading="lazy"
        />
    </div>

    <!-- Informasi Produk -->
    <div class="card-header flex-1 p-3 sm:p-4">
        <h3 class="card-title line-clamp-2 text-sm sm:text-base" title="{{ $title }}">
            {{ $title }}
        </h3>
        <p class="card-description mt-1.5 line-clamp-2 text-xs sm:mt-2 sm:text-sm">
            {{ $description }}
        </p>
    </div>
    
    <!-- Tombol Aksi -->
    <div class="card-footer flex flex-wrap items-center justify-between gap-2 p-3 pt-0 sm:p-4 sm:pt-0">
        <div class="flex items-center gap-2">
            <span class="text-sm font-bold text-foreground sm:text-base">
                Rp {{ number_format($price ?? 250000, 0, ',', '.') }}
            </span>
        </div>
        <a href="{{ $link }}" class="btn min-h-9 px-2 text-xs sm:px-3 sm:text-sm" data-variant="ghost">
            Detail
            <i data-lucide="chevron-right" class="h-4 w-4"></i>
        </a>
    </div>

</div>