<x-layout-component>
    <x-breadcrumb :links="$links" />
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12">

        <!-- Kiri: Galeri Gambar -->
        <div class="flex flex-col gap-4">
            <!-- Gambar Utama -->
            <div class="overflow-hidden rounded-lg bg-muted/50 aspect-square">
                <img src="{{ $product->image ?? 'https://placehold.co/600x600' }}"
                    alt="{{ $product->title ?? 'Gambar Produk' }}" class="w-full h-full object-cover object-center">
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
            <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-foreground mb-2">
                {{ $product->title ?? 'Nama Produk Berkualitas Tinggi' }}
            </h1>

            <!-- Harga & Badge -->
            <div class="flex items-center gap-4 mb-6">
                <span class="text-2xl font-bold text-foreground">
                    Rp {{ number_format($product->price ?? 250000, 0, ',', '.') }}
                </span>
                <span class="badge" data-variant="secondary">In Stock</span>
            </div>

            <!-- Deskripsi Singkat -->
            <p class="text-base text-muted-foreground mb-8 line-clamp-4">
                {{ $product->description ?? 'Ini adalah deskripsi produk yang menjelaskan secara detail mengenai fitur, bahan, dan keunggulan produk ini. Dirancang menggunakan material premium untuk memastikan kenyamanan dan daya tahan jangka panjang.' }}
            </p>

            <!-- Form Add to Cart -->
            <form action="/cart/add" method="POST" class="mb-8">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id ?? 1 }}">

                <div class="flex flex-col gap-4">
                    <!-- Kuantitas -->
                    <div class="flex items-center gap-4">
                        <label for="quantity" class="text-sm font-medium text-foreground w-20">Jumlah</label>
                        <input type="number" id="quantity" name="quantity" value="1" min="1"
                            class="input w-24">
                    </div>

                    <hr class="my-4 border-neutral-200">

                    <!-- Tombol Aksi -->
                    <div class="flex gap-4">
                        <button type="submit" class="btn flex-1" data-variant="default">
                            <i data-lucide="shopping-cart" class="mr-2 h-4 w-4"></i>
                            Tambah ke Keranjang
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
                <div class="p-4 border-b border-neutral-200">
                    <h3 class="font-medium flex items-center gap-2">
                        <i data-lucide="truck" class="h-4 w-4 text-muted-foreground"></i>
                        Informasi Pengiriman
                    </h3>
                    <p class="text-sm text-muted-foreground mt-1">Gratis ongkir untuk pesanan di atas Rp 500.000.
                        Pengiriman dalam 2-3 hari kerja.</p>
                </div>
                <div class="p-4">
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
