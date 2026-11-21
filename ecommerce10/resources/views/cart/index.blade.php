<x-storefront-layout>
    <div class="container" x-data="checkoutFlow({ subtotal: {{ $totalPrice }}, ratesUrl: @js(route('checkout.shipping-rates', absolute: false)) })" @address-selected.window="resetShipping()">
    <x-ui.page-header eyebrow="Belanja Anda" title="Keranjang Belanja" description="Periksa kembali produk sebelum melanjutkan pesanan.">
        <x-slot:actions>
            <span class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-gray-200 bg-white px-3.5 text-sm font-medium text-gray-700"><i data-lucide="shopping-bag" class="h-4 w-4 text-primary-600"></i>{{ $totalQuantity }} Barang</span>
        </x-slot:actions>
    </x-ui.page-header>

    @if (session('success'))
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
            role="status"><i data-lucide="check-circle-2"
                class="mt-0.5 h-4 w-4 shrink-0"></i><span>{{ session('success') }}</span></div>
    @endif
    @if (session('error'))
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
            role="alert"><i data-lucide="alert-circle"
                class="mt-0.5 h-4 w-4 shrink-0"></i><span>{{ session('error') }}</span></div>
    @endif
    @if ($errors->has('cart'))
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            {{ $errors->first('cart') }}</div>
    @endif

    @if ($cartItems->isEmpty())
        <section
            class="flex min-h-[420px] flex-col items-center justify-center rounded-2xl border border-dashed border-gray-300 bg-gray-50/70 px-6 py-14 text-center">
            <span class="bg-primary-50 text-primary-600 flex h-20 w-20 items-center justify-center rounded-2xl"><i
                    data-lucide="shopping-cart" class="h-9 w-9"></i></span>
            <h2 class="mt-6 text-lg font-semibold text-gray-900">Keranjang belanja masih kosong</h2>
            <p class="mt-2 max-w-md text-sm leading-6 text-gray-500">
                @auth
                    Yuk, temukan produk yang kamu butuhkan dan tambahkan ke keranjang.
                @else
                    Masuk ke akunmu untuk melihat keranjang, atau mulai jelajahi produk yang tersedia.
                @endauth
            </p>
            <a href="{{ route('shop') }}"
                class="bg-primary-600 hover:bg-primary-700 focus:ring-primary-200 mt-6 inline-flex items-center justify-center gap-2 rounded-lg px-5 py-3 text-sm font-semibold text-white shadow-sm transition focus:outline-none focus:ring-4">
                Mulai Belanja<i data-lucide="arrow-right" class="h-4 w-4"></i>
            </a>
            @guest
                <a href="{{ route('login', ['redirect_to' => route('cart', absolute: false)]) }}"
                    class="text-primary-600 hover:text-primary-700 mt-3 text-sm font-semibold">Masuk ke akun</a>
            @endguest
        </section>
    @else
        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12 lg:gap-8">
            <section class="min-w-0 space-y-4 lg:col-span-8" aria-label="Daftar produk di keranjang">
                <div class="flex items-center justify-between px-1">
                    <h2 class="font-semibold text-gray-900">Produk Anda</h2><span
                        class="text-sm text-gray-500">{{ $cartItems->count() }} produk</span>
                </div>
                @foreach ($cartItems as $item)
                    @php
                        $product = $item->product;
                        $image =
                            $item->variant?->images->firstWhere('is_primary', true)?->path ??
                            ($item->variant?->images->first()?->path ?? $product->main_image);
                        $unitPrice = $item->variant?->price ?? $product->price;
                        $availableStock = $item->variant?->stock ?? $product->stock;
                        $variantDescription = $item->variant?->optionValues->pluck('label')->implode(' / ');
                        $imageUrl = $image
                            ? (\Illuminate\Support\Str::startsWith($image, ['http://', 'https://', '/'])
                                ? $image
                                : asset('storage/' . $image))
                            : 'https://placehold.co/240x240?text=Produk';
                    @endphp
                    <article
                        class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-gray-300 sm:p-5">
                        <div class="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-center">
                            <a href="{{ route('product-detail', $product->slug) }}"
                                class="h-24 w-24 shrink-0 self-start overflow-hidden rounded-xl border border-gray-100 bg-gray-50 sm:h-28 sm:w-28">
                                <img src="{{ $imageUrl }}" alt="{{ $product->name }}"
                                    class="h-full w-full object-cover" loading="lazy">
                            </a>
                            <div class="flex min-w-0 flex-1 flex-col gap-2">
                                @if ($product->category)
                                    <p class="text-xs font-medium text-gray-500">{{ $product->category->name }}</p>
                                @endif
                                <a href="{{ route('product-detail', $product->slug) }}"
                                    class="hover:text-primary-600 line-clamp-2 font-semibold text-gray-900 transition">{{ $product->name }}</a>
                                @if ($variantDescription)
                                    <p class="text-xs text-gray-500">{{ $variantDescription }}</p>
                                @endif
                                <p class="text-sm font-semibold text-gray-900">Rp
                                    {{ number_format($unitPrice, 0, ',', '.') }} <span
                                        class="font-normal text-gray-500">/ item</span></p>
                                @if ($availableStock > 0)
                                    <p class="flex items-center gap-1.5 text-xs text-green-700"><i
                                            data-lucide="check-circle-2" class="h-3.5 w-3.5"></i>Stok tersedia:
                                    {{ $availableStock }}</p>@else<p class="text-xs font-medium text-red-600">Stok
                                        habis</p>
                                @endif
                            </div>
                            <div
                                class="flex min-w-0 flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4 sm:w-60 sm:shrink-0 sm:flex-col sm:items-end sm:border-0 sm:pt-0">
                                <form action="{{ route('cart.update', $item) }}" method="POST"
                                    class="flex max-w-full items-center gap-1.5" x-data="cartQuantity">
                                    @csrf @method('PATCH')
                                    <label for="quantity-{{ $item->id }}"
                                        class="text-xs font-medium text-gray-500">Qty</label>
                                    <div
                                        class="flex h-9 items-center rounded-lg border border-gray-300 bg-white">
                                        <button type="button" data-button-shape="preserve" @click.prevent="step(-1)" :disabled="decrementDisabled"
                                            class="hover:text-primary-700 focus:ring-primary-500 inline-flex h-9 w-8 shrink-0 items-center justify-center rounded-l-lg text-gray-600 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-inset"
                                            aria-label="Kurangi jumlah {{ $product->name }}">
                                            <i data-lucide="minus" class="h-4 w-4"></i>
                                        </button>
                                        <input id="quantity-{{ $item->id }}" name="quantity" type="number" x-ref="quantity" x-model="value" @input="sync()"
                                            value="{{ $item->quantity }}" min="1"
                                            max="{{ max(1, $availableStock) }}" required inputmode="numeric"
                                            class="qty-input h-9 w-9 border-0 bg-transparent px-0.5 text-center text-sm font-medium text-gray-900 outline-none focus:ring-0 sm:w-14 sm:rounded-lg"
                                            aria-label="Jumlah {{ $product->name }}">
                                        <button type="button" data-button-shape="preserve" @click.prevent="step(1)" :disabled="incrementDisabled"
                                            class="hover:text-primary-700 focus:ring-primary-500 inline-flex h-9 w-8 shrink-0 items-center justify-center rounded-r-lg text-gray-600 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-inset"
                                            aria-label="Tambah jumlah {{ $product->name }}">
                                            <i data-lucide="plus" class="h-4 w-4"></i>
                                        </button>
                                    </div>
                                    <x-ui.button type="submit" size="xs" variant="soft" data-quantity-submit x-bind:disabled="!hasChanged">Perbarui</x-ui.button>
                                </form>
                                <div class="flex w-full items-center justify-between gap-3 sm:justify-end">
                                    <p class="text-sm font-bold text-gray-900">Rp
                                        {{ number_format($unitPrice * $item->quantity, 0, ',', '.') }}</p>
                                    <form action="{{ route('cart.destroy', $item) }}" method="POST" x-data="{}"
                                        @submit="if (!window.confirm(@js('Hapus ' . $product->name . ' dari keranjang?'))) $event.preventDefault()">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-red-200"
                                            aria-label="Hapus {{ $product->name }}" title="Hapus produk"><i
                                                data-lucide="trash-2" class="h-4 w-4"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
                <a href="{{ route('shop') }}"
                    class="text-primary-600 hover:text-primary-700 inline-flex items-center gap-2 px-1 py-2 text-sm font-semibold transition"><i
                        data-lucide="arrow-left" class="h-4 w-4"></i>Lanjut memilih produk</a>
            </section>

            <aside class="min-w-0 lg:col-span-4">
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm lg:sticky lg:top-24">
                    <div class="border-b border-gray-100 px-5 py-4">
                        <h2 class="font-semibold text-gray-900">Ringkasan Belanja</h2>
                        <p class="mt-1 text-sm text-gray-500">Detail total belanja Anda</p>
                    </div>
                    <div class="space-y-4 p-5">
                        <div class="flex justify-between gap-3 text-sm"><span class="text-gray-500">Total
                                barang</span><span class="font-medium text-gray-900">{{ $totalQuantity }} item</span>
                        </div>
                        <div class="flex justify-between gap-3 text-sm"><span
                                class="text-gray-500">Subtotal</span><span
                                class="font-medium text-gray-900">Rp
                                {{ number_format($totalPrice, 0, ',', '.') }}</span></div>
                        <div class="space-y-2 border-t border-dashed border-gray-200 pt-4 text-sm">
                            <div class="flex justify-between gap-3"><span class="text-gray-500">Subtotal</span><span
                                    class="font-medium text-gray-900">Rp
                                    {{ number_format($totalPrice, 0, ',', '.') }}</span></div>
                            <div class="flex justify-between gap-3"><span class="text-gray-500">Ongkos
                                    kirim</span><span id="shipping-cost" class="font-medium text-gray-900" x-text="shippingCost === null ? 'Belum dihitung' : currency(shippingCost)">Belum dihitung</span></div>
                            <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-3"><span
                                    class="font-semibold text-gray-900">Total Pembayaran</span><span
                                    id="checkout-total" data-subtotal="{{ $totalPrice }}"
                                    class="text-lg font-bold text-gray-900" x-text="currency(total)">Rp
                                    {{ number_format($totalPrice, 0, ',', '.') }}</span></div>
                        </div>
                        <form id="checkout-form" action="{{ route('checkout.store') }}" method="POST"
                            class="space-y-3 pt-1" @submit="validateCheckout($event)">
                            @csrf
                            <div class="mb-1 flex items-center justify-between gap-2">
                                <label for="checkout-address" class="text-sm font-semibold text-gray-800">Alamat
                                    Pengiriman</label>
                                <a href="{{ route('addresses.index') }}"
                                    class="text-primary-600 hover:text-primary-700 text-xs font-semibold">Kelola alamat</a>
                            </div>
                            @if ($addresses->isNotEmpty())
                                <x-storefront.address-dropdown
                                    :addresses="$addresses"
                                    name="address_id"
                                    placeholder="Pilih alamat" />
                                @error('address_id')
                                    <p class="text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="text-xs leading-5 text-gray-500">Pastikan kode pos alamat sudah benar. <a
                                        href="{{ route('addresses.create') }}"
                                        class="text-primary-600 hover:text-primary-700 font-semibold">Tambah alamat
                                        baru</a></p>
                            @else
                                <div
                                    class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs leading-5 text-amber-800">
                                    Untuk menghitung ongkir live, simpan alamat lengkap beserta kode pos terlebih
                                    dahulu. <a href="{{ route('addresses.create') }}"
                                        class="font-semibold underline">Tambah alamat</a>.</div>
                            @endif
                            <x-ui.button id="calculate-shipping" type="button" variant="soft" class="w-full" @click="calculate()" x-bind:disabled="@js($addresses->isEmpty()) || loading || !hasAddress">
                                <i data-lucide="truck" class="h-4 w-4"></i><span x-text="loading ? 'Menghitung...' : (quoteId ? 'Hitung Ulang Ongkos Kirim' : 'Hitung Ongkos Kirim')">Hitung Ongkos Kirim</span>
                            </x-ui.button>
                            <p id="shipping-message" class="text-xs leading-5 text-gray-500" role="status" x-text="message">Pilih alamat lalu hitung tarif kurir yang tersedia.</p>
                            <div id="shipping-options" class="space-y-3" aria-live="polite">
                                <template x-for="group in groupedRates" :key="group.code">
                                    <section class="space-y-2">
                                        <div class="flex items-center justify-between gap-2 rounded-xl bg-gray-50 px-3 py-2">
                                            <h3 class="text-sm font-semibold text-gray-800" x-text="group.name"></h3>
                                            <span class="text-xs text-gray-500" x-text="`${group.rates.length} layanan`"></span>
                                        </div>
                                        <template x-for="rate in group.rates" :key="rate.rate_key">
                                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 p-3 text-sm transition hover:border-primary-300" :class="rateKey === rate.rate_key ? 'border-primary-500 bg-primary-50 ring-1 ring-primary-100' : 'bg-white'">
                                                <input type="radio" name="shipping-choice" :value="rate.rate_key" :checked="rateKey === rate.rate_key" @change="selectRate(rate)" class="mt-1 accent-primary-600">
                                                <span class="min-w-0 flex-1">
                                                    <span class="block font-semibold text-gray-900" x-text="rate.service_name"></span>
                                                    <span class="block text-xs text-gray-500" x-text="rate.duration ? `Estimasi: ${rate.duration}` : 'Estimasi mengikuti kurir'"></span>
                                                </span>
                                                <span class="whitespace-nowrap font-semibold text-gray-900" x-text="currency(rate.price)"></span>
                                            </label>
                                        </template>
                                    </section>
                                </template>
                            </div>
                            <input type="hidden" name="shipping_quote_id" id="shipping-quote-id" :value="quoteId">
                            <input type="hidden" name="shipping_rate_key" id="shipping-rate-key" :value="rateKey">
                            @error('shipping_rate_key')
                                <p class="text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <x-ui.button id="place-order" type="submit" size="lg" class="w-full" x-bind:disabled="!canSubmit">Buat Pesanan<i data-lucide="arrow-right" class="h-4 w-4"></i></x-ui.button>
                        </form>
                        <a href="{{ route('shop') }}"
                            class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Lanjut
                            Belanja</a>
                    </div>
                </div>
            </aside>
        </div>

    @endif
    </div>
</x-storefront-layout>
