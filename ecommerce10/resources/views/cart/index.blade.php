<x-layout-component>
    <!-- Header Halaman -->
    <div class="flex items-center gap-4 mb-8">
        <h1 class="text-3xl font-bold tracking-tight text-foreground">Keranjang Belanja</h1>
        <span class="badge" data-variant="secondary">3 Item</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">

        <!-- Kiri: Daftar Produk di Keranjang -->
        <div class="lg:col-span-8 flex flex-col gap-6">

            <!-- Card Daftar Item -->
            <div class="card">
                <div class="p-6 flex flex-col gap-6">

                    {{-- Contoh Looping Item Laravel --}}
                    {{-- @forelse ($cartItems as $item) --}}

                    <!-- Item 1 -->
                    <div
                        class="flex flex-col sm:flex-row items-start sm:items-center gap-4 pb-6 border-b last:border-0 last:pb-0">
                        <!-- Gambar Produk -->
                        <div class="h-24 w-24 shrink-0 overflow-hidden rounded-md border bg-muted/50">
                            <img src="https://placehold.co/150x150" alt="Produk 1" class="h-full w-full object-cover">
                        </div>

                        <!-- Detail Produk -->
                        <div class="flex flex-1 flex-col justify-between">
                            <div>
                                <h3 class="font-medium text-foreground line-clamp-1">Kemeja Pria Premium Lengan Panjang
                                </h3>
                                <p class="text-sm text-muted-foreground mt-1">Varian: Hitam, Ukuran: L</p>
                            </div>
                            <div class="mt-2 font-semibold">
                                Rp 250.000
                            </div>
                        </div>

                        <!-- Aksi (Kuantitas & Hapus) -->
                        <div
                            class="flex items-center gap-4 w-full sm:w-auto justify-between sm:justify-end mt-4 sm:mt-0">
                            <!-- Kontrol Kuantitas -->
                            <div class="flex items-center gap-2">
                                <button class="button h-8 w-8" data-variant="outline" data-size="icon"
                                    aria-label="Kurangi">
                                    <i data-lucide="minus" class="h-3 w-3"></i>
                                </button>
                                <input type="number" value="1" min="1"
                                    class="input h-8 w-14 text-center px-2">
                                <button class="button h-8 w-8" data-variant="outline" data-size="icon"
                                    aria-label="Tambah">
                                    <i data-lucide="plus" class="h-3 w-3"></i>
                                </button>
                            </div>

                            <!-- Hapus Item -->
                            <button class="button text-destructive hover:bg-destructive/10 hover:text-destructive"
                                data-variant="ghost" data-size="icon" aria-label="Hapus item">
                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Item 2 -->
                    <div
                        class="flex flex-col sm:flex-row items-start sm:items-center gap-4 pb-6 border-b last:border-0 last:pb-0">
                        <div class="h-24 w-24 shrink-0 overflow-hidden rounded-md border bg-muted/50">
                            <img src="https://placehold.co/150x150" alt="Produk 2" class="h-full w-full object-cover">
                        </div>
                        <div class="flex flex-1 flex-col justify-between">
                            <div>
                                <h3 class="font-medium text-foreground line-clamp-1">Sneakers Olahraga Ringan</h3>
                                <p class="text-sm text-muted-foreground mt-1">Varian: Putih, Ukuran: 42</p>
                            </div>
                            <div class="mt-2 font-semibold">
                                Rp 450.000
                            </div>
                        </div>
                        <div
                            class="flex items-center gap-4 w-full sm:w-auto justify-between sm:justify-end mt-4 sm:mt-0">
                            <div class="flex items-center gap-2">
                                <button class="button h-8 w-8" data-variant="outline" data-size="icon"><i
                                        data-lucide="minus" class="h-3 w-3"></i></button>
                                <input type="number" value="2" min="1"
                                    class="input h-8 w-14 text-center px-2">
                                <button class="button h-8 w-8" data-variant="outline" data-size="icon"><i
                                        data-lucide="plus" class="h-3 w-3"></i></button>
                            </div>
                            <button class="button text-destructive hover:bg-destructive/10 hover:text-destructive"
                                data-variant="ghost" data-size="icon">
                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                            </button>
                        </div>
                    </div>

                    {{-- State Jika Keranjang Kosong --}}
                    {{-- @empty
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <div class="h-20 w-20 bg-muted rounded-full flex items-center justify-center mb-4">
                                <i data-lucide="shopping-cart" class="h-10 w-10 text-muted-foreground"></i>
                            </div>
                            <h3 class="text-lg font-semibold mb-2">Keranjang belanja Anda kosong</h3>
                            <p class="text-muted-foreground mb-6">Sepertinya Anda belum menambahkan produk apapun.</p>
                            <a href="/shop" class="button" data-variant="default">Mulai Belanja</a>
                        </div>
                        @endforelse --}}

                </div>
            </div>
        </div>

        <!-- Kanan: Ringkasan Pesanan (Order Summary) -->
        <div class="lg:col-span-4">
            <div class="card sticky top-24">
                <div class="card-header border-b">
                    <h2 class="card-title text-lg">Ringkasan Belanja</h2>
                </div>

                <div class="card-content py-6 flex flex-col gap-4">
                    <!-- Rincian Harga -->
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">Subtotal (3 Barang)</span>
                        <span class="font-medium text-foreground">Rp 1.150.000</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">Estimasi Diskon</span>
                        <span class="font-medium text-green-600">- Rp 50.000</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">Pajak (11%)</span>
                        <span class="font-medium text-foreground">Rp 121.000</span>
                    </div>

                    <hr class="border-muted my-2">

                    <!-- Total Harga -->
                    <div class="flex justify-between items-center">
                        <span class="text-base font-bold text-foreground">Total Harga</span>
                        <span class="text-xl font-bold text-foreground">Rp 1.221.000</span>
                    </div>
                </div>

                <div class="card-footer pt-0 flex flex-col gap-3">
                    <a href="/checkout" class="button w-full" data-variant="default">
                        Beli Sekarang (3)
                        <i data-lucide="arrow-right" class="ml-2 h-4 w-4"></i>
                    </a>
                    <a href="/shop" class="button w-full" data-variant="outline">
                        Lanjut Belanja
                    </a>
                </div>
            </div>
        </div>

    </div>
</x-layout-component>
