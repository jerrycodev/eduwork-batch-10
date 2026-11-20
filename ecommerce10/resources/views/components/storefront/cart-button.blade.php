<a title="Buka keranjang belanja" href="{{ route('cart') }}"
    class="hover:border-primary-200 hover:bg-primary-50 hover:text-primary-700 relative inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 text-gray-600 transition"
    aria-label="Buka keranjang belanja">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
        stroke-linejoin="round" class="lucide lucide-shopping-bag preview-icon">
        <path d="M16 10a4 4 0 0 1-8 0" />
        <path d="M3.103 6.034h17.794" />
        <path
            d="M3.4 5.467a2 2 0 0 0-.4 1.2V20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.667a2 2 0 0 0-.4-1.2l-2-2.667A2 2 0 0 0 17 2H7a2 2 0 0 0-1.6.8z" />
    </svg>
    <span class="sr-only">Keranjang Belanja</span>
    <span
        class="absolute -right-1 -top-1 inline-flex items-center justify-center rounded-full bg-primary-600 w-4 h-4 text-xs font-bold leading-none text-white">
        {{ $itemCount }}
    </span>
</a>
