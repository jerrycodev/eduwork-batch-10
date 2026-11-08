<x-layout-component>
    <x-breadcrumb :links="$links" />
    <div class="grid grid-cols-1 gap-6 sm:gap-8 md:grid-cols-2 lg:gap-12">

        <!-- Kiri: Galeri Gambar -->
        <div class="flex flex-col gap-4">
            <!-- Gambar Utama -->
            <div class="overflow-hidden rounded-lg bg-muted/50 aspect-square">
                <img src="{{ $product->image ?? 'https://placehold.co/600x600' }}"
                    alt="{{ $product->name ?? 'Gambar Produk' }}" class="w-full h-full object-cover object-center">
            </div>

            <!-- Thumbnail Placeholder (Jika ada multi-gambar) -->
            <div class="grid grid-cols-4 gap-2 sm:gap-4">
                @for ($i = 0; $i < 4; $i++)
                    <button
                        class="overflow-hidden rounded-md border-2 border-transparent hover:border-primary transition-colors aspect-square bg-muted">
                        <img src="https://placehold.co/150x150" alt="Thumbnail {{ $i + 1 }}"
                            class="w-full h-full object-cover">
                    </button>
                @endfor
            </div>
        </div>

        <!-- Kanan: Detail Produk -->
        <div class="flex flex-col">
            <h1 class="mb-2 text-2xl font-bold tracking-tight text-foreground sm:text-3xl lg:text-4xl">
                {{ $product->name ?? 'Nama Produk Berkualitas Tinggi' }}
            </h1>

            <!-- Harga & Badge -->
            <div class="mb-5 flex flex-wrap items-center gap-3 sm:mb-6 sm:gap-4">
                <span class="text-xl font-bold text-foreground sm:text-2xl">
                    Rp {{ number_format($product->price ?? 250000, 0, ',', '.') }}
                </span>
                <span class="badge" data-variant="secondary">
                    {{ $product->stock > 0 ? "Stok: {$product->stock}" : "Stok Habis" }}
                </span>
            </div>

            <!-- Deskripsi Singkat -->
            <p class="mb-6 text-sm text-muted-foreground sm:mb-8 sm:text-base line-clamp-4">
                {{ $product->description ?? 'Ini adalah deskripsi produk yang menjelaskan secara detail mengenai fitur, bahan, dan keunggulan produk ini. Dirancang menggunakan material premium untuk memastikan kenyamanan dan daya tahan jangka panjang.' }}
            </p>

            <!-- Form Add to Cart -->
            <form action="{{ route('cart.store', $product) }}" method="POST" class="mb-8">
                @csrf

                <div class="flex flex-col gap-4">
                    <div class="flex items-center gap-4">
                        <label for="quantity" class="w-20 text-sm font-medium text-foreground">Jumlah</label>
                        <input type="number" id="quantity" name="quantity" value="{{ old('quantity', 1) }}" min="1"
                            max="{{ $product->stock }}" required class="input w-24">
                    </div>
                    @error('quantity')
                        <p class="text-sm text-destructive">{{ $message }}</p>
                    @enderror

                    <hr class="my-4 border-neutral-200">

                    <div class="flex flex-col gap-3 sm:flex-row sm:gap-4">
                        <button type="submit" class="btn min-h-11 flex-1" data-variant="default"
                            @disabled($product->stock < 1)>
                            <i data-lucide="shopping-cart" class="mr-2 h-4 w-4"></i>
                            {{ $product->stock > 0 ? 'Tambah ke Keranjang' : 'Stok Habis' }}
                        </button>
                        <button type="button" class="btn" data-variant="outline" data-size="icon"
                            aria-label="Add to wishlist">
                            <i data-lucide="heart" class="h-5 w-5"></i>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Informasi Tambahan (Spesifikasi, Pengiriman) -->
            <div class="border border-neutral-200 rounded-lg bg-card">
                <div class="border-b border-neutral-200 p-4 sm:p-5">
                    <h3 class="font-medium flex items-center gap-2">
                        <i data-lucide="truck" class="h-4 w-4 text-muted-foreground"></i>
                        Informasi Pengiriman
                    </h3>
                    <p class="text-sm text-muted-foreground mt-1">Gratis ongkir untuk pesanan di atas Rp 500.000.
                        Pengiriman dalam 2-3 hari kerja.</p>
                </div>
                <div class="p-4 sm:p-5">
                    <h3 class="font-medium flex items-center gap-2">
                        <i data-lucide="shield-check" class="h-4 w-4 text-muted-foreground"></i>
                        Garansi
                    </h3>
                    <p class="text-sm text-muted-foreground mt-1">Garansi resmi 1 tahun. Retur gratis dalam 7 hari jika
                        barang tidak sesuai.</p>
                </div>
            </div>

        </div>
    </div>
</x-layout-component>
