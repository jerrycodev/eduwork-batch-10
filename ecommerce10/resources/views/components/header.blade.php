<header class="sticky top-0 z-40 border-b border-gray-100 bg-white/95 backdrop-blur">
    <div x-data="{ open: false }" class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex min-h-16 items-center justify-between gap-4">
            <x-logo />

            <nav class="hidden items-center gap-6 lg:flex" aria-label="Navigasi utama">
                <a href="{{ route('home') }}" @class([
                    'text-sm font-medium transition-colors hover:text-indigo-600',
                    'text-indigo-600' => request()->routeIs('home'),
                    'text-gray-600' => !request()->routeIs('home'),
                ])>Home</a>
                <a href="{{ route('shop') }}" @class([
                    'text-sm font-medium transition-colors hover:text-indigo-600',
                    'text-indigo-600' => request()->routeIs('shop'),
                    'text-gray-600' => !request()->routeIs('shop'),
                ])>Shop</a>
                @auth
                    @unlessadmin
                        <a href="{{ route('wishlist.index') }}" @class([
                            'text-sm font-medium transition-colors hover:text-indigo-600',
                            'text-indigo-600' => request()->routeIs('wishlist.*'),
                            'text-gray-600' => !request()->routeIs('wishlist.*'),
                        ])>Wishlist</a>
                    @endunlessadmin
                    <a href="{{ route('orders.customer.index') }}" @class([
                        'text-sm font-medium transition-colors hover:text-indigo-600',
                        'text-indigo-600' => request()->routeIs('orders.customer.*'),
                        'text-gray-600' => !request()->routeIs('orders.customer.*'),
                    ])>Pesanan</a>
                @endauth
            </nav>

            <form action="{{ route('shop') }}" method="GET" role="search"
                class="hidden min-w-0 max-w-md flex-1 md:block lg:max-w-lg">
                <label for="header-search" class="sr-only">Cari produk</label>
                <div class="relative">
                    <i data-lucide="search"
                        class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                    <input id="header-search" type="search" name="search" value="{{ request('search') }}"
                        placeholder="Cari produk yang kamu inginkan..."
                        class="h-10 w-full rounded-full border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 hover:border-gray-300 focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10">
                </div>
            </form>

            <div class="hidden items-center gap-3 md:flex">
                @unlessadmin
                    <a href="{{ route('cart') }}"
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-full border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                        aria-label="Buka keranjang belanja">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-shopping-bag preview-icon">
                            <path d="M16 10a4 4 0 0 1-8 0" />
                            <path d="M3.103 6.034h17.794" />
                            <path
                                d="M3.4 5.467a2 2 0 0 0-.4 1.2V20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.667a2 2 0 0 0-.4-1.2l-2-2.667A2 2 0 0 0 17 2H7a2 2 0 0 0-1.6.8z" />
                        </svg>
                        <span>Keranjang</span>
                    </a>
                @endunlessadmin
                @auth
                    <a href="{{ route('profile.edit') }}"
                        class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-4 py-2.5 text-sm font-medium text-indigo-700 transition hover:bg-indigo-100">
                        <i data-lucide="user-round" class="h-4 w-4"></i>{{ Str::limit(Auth::user()->name, 18) }}
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="inline-flex items-center justify-center rounded-full bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">Masuk</a>
                @endauth
            </div>

            <button type="button" @click="open = !open" :aria-expanded="open.toString()"
                aria-controls="mobile-navigation" aria-label="Buka menu navigasi"
                class="inline-flex h-10 w-10 items-center justify-center rounded-full text-gray-600 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 md:hidden">
                <i x-show="!open" data-lucide="menu" class="h-5 w-5"></i>
                <i x-show="open" x-cloak data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <div class="pb-3 md:hidden">
            <form action="{{ route('shop') }}" method="GET" role="search">
                <label for="mobile-header-search" class="sr-only">Cari produk</label>
                <div class="relative">
                    <i data-lucide="search"
                        class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                    <input id="mobile-header-search" type="search" name="search" value="{{ request('search') }}"
                        placeholder="Cari produk..."
                        class="h-10 w-full rounded-full border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10">
                </div>
            </form>
        </div>

        <div id="mobile-navigation" x-show="open" x-transition.opacity x-cloak
            class="border-t border-gray-100 py-3 md:hidden">
            <div class="flex flex-col gap-1">
                <a href="{{ route('home') }}"
                    class="rounded-xl px-3 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-indigo-50 hover:text-indigo-700">Home</a>
                <a href="{{ route('shop') }}"
                    class="rounded-xl px-3 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-indigo-50 hover:text-indigo-700">Shop</a>
                <a href="{{ route('cart') }}"
                    class="flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-indigo-50 hover:text-indigo-700">
                    Keranjang</a>
                @auth
                    @unlessadmin
                        <a href="{{ route('wishlist.index') }}" @class([
                            'text-sm font-medium transition-colors hover:text-indigo-600',
                            'text-indigo-600' => request()->routeIs('wishlist.*'),
                            'text-gray-600' => !request()->routeIs('wishlist.*'),
                        ])>Wishlist</a>
                    @endunlessadmin
                    <a href="{{ route('orders.customer.index') }}"
                        class="rounded-xl px-3 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-indigo-50 hover:text-indigo-700">Pesanan</a>
                    <a href="{{ route('profile.edit') }}"
                        class="rounded-xl px-3 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-indigo-50 hover:text-indigo-700">Profil</a>
                @else
                    <a href="{{ route('login') }}"
                        class="mt-2 inline-flex items-center justify-center rounded-full bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">Masuk</a>
                @endauth
            </div>
        </div>
    </div>
</header>
