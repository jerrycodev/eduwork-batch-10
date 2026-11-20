<footer class="border-t border-gray-200/80 bg-white">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-10 px-4 py-12 sm:px-6 md:flex-row md:justify-between lg:px-8">
        <div class="max-w-md">
            <x-logo />
            <p class="mt-4 max-w-sm text-sm leading-6 text-gray-500">Temukan produk pilihan dengan pengalaman belanja yang mudah, nyaman, dan terpercaya.</p>
        </div>
        <div class="grid grid-cols-2 gap-10 sm:gap-16">
            <div>
                <h2 class="mb-4 text-sm font-semibold text-gray-900">Navigasi</h2>
                <ul class="space-y-3 text-sm text-gray-500">
                    <li><a href="{{ route('home') }}" class="transition hover:text-primary-600">Home</a></li>
                    <li><a href="{{ route('shop') }}" class="transition hover:text-primary-600">Shop</a></li>
                    <li><a href="{{ route('cart') }}" class="transition hover:text-primary-600">Keranjang</a></li>
                </ul>
            </div>
            <div>
                <h2 class="mb-4 text-sm font-semibold text-gray-900">Akun</h2>
                <ul class="space-y-3 text-sm text-gray-500">
                    @auth
                        <li><a href="{{ route('profile.edit') }}" class="transition hover:text-primary-600">Profil</a></li>
                        <li><a href="{{ route('orders.customer.index') }}" class="transition hover:text-primary-600">Pesanan</a></li>
                    @else
                        <li><a href="{{ route('login') }}" class="transition hover:text-primary-600">Masuk</a></li>
                        <li><a href="{{ route('register') }}" class="transition hover:text-primary-600">Daftar</a></li>
                    @endauth
                </ul>
            </div>
        </div>
    </div>
    <div class="border-t border-gray-200/80">
        <p class="mx-auto max-w-7xl px-4 py-5 text-center text-xs text-gray-400 sm:px-6 lg:px-8">© {{ now()->year }} {{ config('app.name') }}. Hak cipta dilindungi.</p>
    </div>
</footer>
