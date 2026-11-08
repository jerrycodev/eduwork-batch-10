<footer class="bg-background">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-12 lg:px-8">

        <!-- Grid Footer Atas -->
        <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4 lg:gap-10">

            <!-- Kolom 1: Info Brand -->
            <div class="flex flex-col gap-4">
                <a href="/" class="flex items-center gap-2 font-bold text-xl text-foreground">
                    <i data-lucide="shopping-bag" class="h-6 w-6"></i>
                    <span>{{config('app.name')}}</span>
                </a>
                <p class="text-sm text-muted-foreground leading-relaxed">
                    Platform belanja online terpercaya yang menyediakan berbagai macam produk berkualitas dengan harga
                    terbaik untuk kebutuhan sehari-hari Anda.
                </p>
                <div class="mt-2 flex items-center gap-4">
                    <a href="#" class="text-muted-foreground hover:text-foreground transition-colors"
                        aria-label="Facebook">
                        <i data-lucide="facebook" class="h-5 w-5"></i>
                    </a>
                    <a href="#" class="text-muted-foreground hover:text-foreground transition-colors"
                        aria-label="Instagram">
                        <i data-lucide="instagram" class="h-5 w-5"></i>
                    </a>
                    <a href="#" class="text-muted-foreground hover:text-foreground transition-colors"
                        aria-label="Twitter">
                        <i data-lucide="twitter" class="h-5 w-5"></i>
                    </a>
                </div>
            </div>

            <!-- Kolom 2: Tautan Cepat -->
            <div>
                <h3 class="font-semibold text-foreground mb-4">Kategori Populer</h3>
                <ul class="flex flex-col space-y-3 text-sm text-muted-foreground">
                    <li><a href="#" class="hover:text-foreground transition-colors">Pakaian Pria</a></li>
                    <li><a href="#" class="hover:text-foreground transition-colors">Pakaian Wanita</a></li>
                    <li><a href="#" class="hover:text-foreground transition-colors">Sepatu & Sneakers</a></li>
                    <li><a href="#" class="hover:text-foreground transition-colors">Aksesoris</a></li>
                    <li><a href="#" class="hover:text-foreground transition-colors">Promo & Diskon</a></li>
                </ul>
            </div>

            <!-- Kolom 3: Layanan Pelanggan -->
            <div>
                <h3 class="font-semibold text-foreground mb-4">Layanan Pelanggan</h3>
                <ul class="flex flex-col space-y-3 text-sm text-muted-foreground">
                    <li><a href="#" class="hover:text-foreground transition-colors">Bantuan & FAQ</a></li>
                    <li><a href="#" class="hover:text-foreground transition-colors">Lacak Pesanan</a></li>
                    <li><a href="#" class="hover:text-foreground transition-colors">Pengembalian Barang</a></li>
                    <li><a href="#" class="hover:text-foreground transition-colors">Syarat & Ketentuan</a></li>
                    <li><a href="#" class="hover:text-foreground transition-colors">Kebijakan Privasi</a></li>
                </ul>
            </div>

            <!-- Kolom 4: Newsletter -->
            <div>
                <h3 class="font-semibold text-foreground mb-4">Berlangganan Newsletter</h3>
                <p class="text-sm text-muted-foreground mb-4">
                    Dapatkan informasi promo terbaru dan penawaran eksklusif langsung ke email Anda.
                </p>
                <form action="/subscribe" method="POST" class="flex flex-col gap-2">
                    @csrf
                    <div class="flex flex-wrap items-center gap-2">
                        <input type="email" name="email" placeholder="Alamat email Anda" class="input flex-1"
                            required>
                        <button type="submit" class="btn" data-variant="outline">
                            Kirim
                        </button>
                    </div>
                </form>
            </div>

        </div>

        <!-- Garis Pemisah -->
        <hr class="my-8 border-neutral-200" />

        <!-- Footer Bawah -->
        <div class="flex flex-col items-start justify-between gap-4 text-sm text-muted-foreground sm:flex-row sm:items-center">
            <p>
                &copy; {{ date('Y') }} Ecommerce. All rights reserved.
            </p>
            <div class="flex items-center gap-4">
                <!-- Contoh Icon Metode Pembayaran (Bisa diganti gambar SVG jika ada) -->
                <i data-lucide="credit-card" class="h-6 w-6"></i>
                <i data-lucide="wallet" class="h-6 w-6"></i>
                <i data-lucide="landmark" class="h-6 w-6"></i>
            </div>
        </div>

    </div>
</footer>
