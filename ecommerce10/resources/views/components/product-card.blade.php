<div class="card flex flex-col overflow-hidden group">
    
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
    <div class="card-header flex-1 p-4">
        <h3 class="card-title text-lg line-clamp-1" title="{{ $title }}">
            {{ $title }}
        </h3>
        <p class="card-description mt-2 text-sm line-clamp-2">
            {{ $description }}
        </p>
    </div>
    
    <!-- Tombol Aksi -->
    <div class="card-footer p-4 pt-0 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="text-lg font-bold text-foreground">
                Rp {{ number_format($price ?? 250000, 0, ',', '.') }}
            </span>
        </div>
        <a href="{{ $link }}" class="btn" data-variant="ghost">
            Detail
            <i data-lucide="chevron-right" class="h-4 w-4"></i>
        </a>
    </div>

</div>