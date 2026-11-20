@if (auth('customer')->check() &&
        auth('customer')->user()->role === 'customer' &&
        !auth('customer')->user()->hasVerifiedEmail())
    <div class="border-b border-amber-200 bg-amber-50 text-amber-950" role="status" aria-live="polite">
        <div
            class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <div class="flex min-w-0 items-start gap-3">
                <span
                    class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                    <i data-lucide="mail-warning" class="h-5 w-5"></i>
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-semibold">Verifikasi email Anda</p>
                    <p class="mt-0.5 text-sm text-amber-800">
                        Tautan verifikasi telah dikirim ke
                        <span class="font-semibold">{{ auth('customer')->user()->email }}</span>.
                        Verifikasi email untuk mengaktifkan checkout.
                    </p>
                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-1 text-xs font-medium text-green-700">Email verifikasi baru berhasil dikirim.
                            Silakan periksa kotak masuk atau folder spam.</p>
                    @endif
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-3 pl-12 sm:pl-0">
                <a href="{{ route('verification.notice') }}"
                    class="text-sm font-semibold text-amber-900 underline decoration-amber-400 underline-offset-4 hover:text-amber-700">
                    Lihat instruksi
                </a>
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center justify-center rounded-full bg-amber-700 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-amber-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-600 focus-visible:ring-offset-2">
                        Kirim ulang email
                    </button>
                </form>
            </div>
        </div>
    </div>
@endif

<header id="header"
    class="sticky top-0 z-40 w-full border-b border-gray-200/80 bg-white/95 backdrop-blur-xl"
    x-data="{ open: false, openSearch: false }">
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex min-h-16 items-center justify-between gap-4">
            <x-logo />

            <nav class="hidden items-center gap-6 lg:flex" aria-label="Navigasi utama">
                <a href="{{ route('home') }}" @class([
                    'text-sm font-medium transition-colors hover:text-primary-600',
                    'text-primary-600' => request()->routeIs('home'),
                    'text-gray-600' => !request()->routeIs('home'),
                ])>Home</a>
                <a href="{{ route('shop') }}" @class([
                    'text-sm font-medium transition-colors hover:text-primary-600',
                    'text-primary-600' => request()->routeIs('shop'),
                    'text-gray-600' => !request()->routeIs('shop'),
                ])>Shop</a>
                @auth
                    @unlessadmin
                        <a href="{{ route('wishlist.index') }}" @class([
                            'text-sm font-medium transition-colors hover:text-primary-600',
                            'text-primary-600' => request()->routeIs('wishlist.*'),
                            'text-gray-600' => !request()->routeIs('wishlist.*'),
                        ])>Wishlist</a>
                    @endunlessadmin
                    <a href="{{ route('orders.customer.index') }}" @class([
                        'text-sm font-medium transition-colors hover:text-primary-600',
                        'text-primary-600' => request()->routeIs('orders.customer.*'),
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
                        class="focus:border-primary-500 focus:ring-primary-500/10 h-10 w-full rounded-full border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 hover:border-gray-300 focus:bg-white focus:ring-4">
                </div>
            </form>

            <div class="hidden items-center gap-3 md:flex">
                @unlessadmin
                    <x-cart-button />
                @endunlessadmin
                @auth
                    <x-notification-link />
                    {{-- <a href="{{ route('profile.edit') }}"
                        class="inline-flex items-center gap-2 rounded-full bg-primary-50 px-4 py-2.5 text-sm font-medium text-primary-700 transition hover:bg-primary-100">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-user-round preview-icon">
                            <circle cx="12" cy="8" r="5" />
                            <path d="M20 21a8 8 0 0 0-16 0" />
                        </svg>
                        {{ Str::limit(Auth::user()->name, 18) }}
                    </a> --}}
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button
                                class="bg-primary-50 text-primary-700 hover:bg-primary-100 inline-flex items-center rounded-full border border-transparent px-4 py-2.5 text-sm font-medium leading-4 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                        viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="lucide lucide-user-round preview-icon">
                                        <circle cx="12" cy="8" r="5" />
                                        <path d="M20 21a8 8 0 0 0-16 0" />
                                    </svg>
                                    <span>{{ Auth::user()->name }}</span>
                                </div>

                                <div class="ms-1">
                                    <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Profile') }}
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('addresses.index')">
                                {{ __('Addresses') }}
                            </x-dropdown-link>
                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}" x-data="{}" @submit="if (!window.confirm('Apakah Anda yakin ingin keluar?')) $event.preventDefault()">
                                @csrf

                                <button type="submit"
                                    class="hover:bg-primary-50 hover:text-primary-700 focus:bg-primary-50 focus:text-primary-700 block w-full rounded-lg px-3 py-2 text-start text-sm text-gray-600 transition focus:outline-none">
                                    {{ __('Log Out') }}
                                </button>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <a href="{{ route('login') }}"
                        class="bg-primary-600 hover:bg-primary-700 focus-visible:ring-primary-500 inline-flex items-center justify-center rounded-full px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2">Masuk</a>
                @endauth
            </div>

            <div class="flex items-center gap-2 md:hidden">
                {{-- Search Menu Button --}}
                <button type="button" @click="openSearch = !openSearch; open = false"
                    :aria-expanded="openSearch.toString()"
                    aria-controls="search-menu" aria-label="Buka pencarian"
                    class="focus-visible:ring-primary-500 inline-flex h-10 w-10 items-center justify-center rounded-full text-gray-600 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2">
                    <i x-show="!openSearch" data-lucide="search" class="h-5 w-5"></i>
                    <i x-show="openSearch" x-cloak data-lucide="x" class="h-5 w-5"></i>
                </button>

                {{-- Notification --}}
                <x-notification-link />
                {{-- Mobile Menu Button --}}
                <button type="button" @click="open = !open; openSearch = false" :aria-expanded="open.toString()"
                    aria-controls="mobile-navigation" aria-label="Buka menu navigasi"
                    class="focus-visible:ring-primary-500 inline-flex h-10 w-10 items-center justify-center rounded-full text-gray-600 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2">
                    <i x-show="!open" data-lucide="menu" class="h-5 w-5"></i>
                    <i x-show="open" x-cloak data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
        </div>

        <div class="pb-3 md:hidden" id="search-menu" x-show="openSearch" x-transition.opacity x-cloak>
            <form action="{{ route('shop') }}" method="GET" role="search">
                <label for="mobile-header-search" class="sr-only">Cari produk</label>
                <div class="relative">
                    <i data-lucide="search"
                        class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                    <input id="mobile-header-search" type="search" name="search" value="{{ request('search') }}"
                        placeholder="Cari produk..."
                        class="focus:border-primary-500 focus:ring-primary-500/10 h-10 w-full rounded-full border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:bg-white focus:ring-4">
                </div>
            </form>
        </div>

        <div id="mobile-navigation" x-show="open" x-transition.opacity x-cloak
            class="border-t border-gray-100 py-3 md:hidden">
            <div class="flex flex-col gap-1">
                <a href="{{ route('home') }}"
                    class="hover:bg-primary-50 hover:text-primary-700 rounded-xl px-3 py-2.5 text-sm font-medium text-gray-700 transition">Home</a>
                <a href="{{ route('shop') }}"
                    class="hover:bg-primary-50 hover:text-primary-700 rounded-xl px-3 py-2.5 text-sm font-medium text-gray-700 transition">Shop</a>
                <a href="{{ route('cart') }}"
                    class="hover:bg-primary-50 hover:text-primary-700 flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium text-gray-700 transition">
                    Keranjang</a>
                @auth
                    @unlessadmin
                        <a href="{{ route('wishlist.index') }}" @class([
                            'hover:bg-primary-50 hover:text-primary-700 rounded-xl px-3 py-2.5 text-sm font-medium transition',
                            'bg-primary-50 text-primary-700' => request()->routeIs('wishlist.*'),
                            'text-gray-700' => !request()->routeIs('wishlist.*'),
                        ])>Wishlist</a>
                    @endunlessadmin
                    <a href="{{ route('orders.customer.index') }}"
                        class="hover:bg-primary-50 hover:text-primary-700 rounded-xl px-3 py-2.5 text-sm font-medium text-gray-700 transition">Pesanan</a>
                    <a href="{{ route('profile.edit') }}"
                        class="hover:bg-primary-50 hover:text-primary-700 rounded-xl px-3 py-2.5 text-sm font-medium text-gray-700 transition">Profil</a>
                @else
                    <a href="{{ route('login') }}"
                        class="bg-primary-600 hover:bg-primary-700 mt-2 inline-flex items-center justify-center rounded-full px-5 py-2.5 text-sm font-semibold text-white transition">Masuk</a>
                @endauth
            </div>
        </div>
    </div>
</header>
