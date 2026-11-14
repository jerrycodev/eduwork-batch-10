<x-layout-component>
    <div class="mb-7 flex flex-wrap items-end justify-between gap-4 sm:mb-8">
        <div>
            <p class="mb-2 text-sm font-semibold text-indigo-600">Your shopping bag</p>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Keranjang Belanja</h1>
            <p class="mt-1.5 text-sm text-gray-500">Periksa kembali produk sebelum melanjutkan pesanan.</p>
        </div>
        <span
            class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 shadow-sm">
            <i data-lucide="shopping-bag" class="h-4 w-4 text-indigo-600"></i>{{ $totalQuantity }} Barang
        </span>
    </div>

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
            <span class="flex h-20 w-20 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600"><i
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
                class="mt-6 inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200">
                Mulai Belanja<i data-lucide="arrow-right" class="h-4 w-4"></i>
            </a>
            @guest
                <a href="{{ route('login', ['redirect_to' => route('cart', absolute: false)]) }}"
                    class="mt-3 text-sm font-semibold text-indigo-600 hover:text-indigo-700">Masuk ke akun</a>
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
                        $image = $product->image;
                        $imageUrl = $image
                            ? (\Illuminate\Support\Str::startsWith($image, ['http://', 'https://', '/'])
                                ? $image
                                : asset('storage/' . $image))
                            : 'https://placehold.co/240x240?text=Produk';
                    @endphp
                    <article
                        class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-gray-300 sm:p-5">
                        <div class="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-center">
                            <a href="{{ route('product-detail', $product) }}"
                                class="h-24 w-24 shrink-0 self-start overflow-hidden rounded-xl border border-gray-100 bg-gray-50 sm:h-28 sm:w-28">
                                <img src="{{ $imageUrl }}" alt="{{ $product->name }}"
                                    class="h-full w-full object-cover" loading="lazy">
                            </a>
                            <div class="flex min-w-0 flex-1 flex-col gap-2">
                                @if ($product->category)
                                    <p class="text-xs font-medium text-gray-500">{{ $product->category->name }}</p>
                                @endif
                                <a href="{{ route('product-detail', $product) }}"
                                    class="line-clamp-2 font-semibold text-gray-900 transition hover:text-indigo-600">{{ $product->name }}</a>
                                <p class="text-sm font-semibold text-gray-900">Rp
                                    {{ number_format($product->price, 0, ',', '.') }} <span
                                        class="font-normal text-gray-500">/ item</span></p>
                                @if ($product->stock > 0)
                                    <p class="flex items-center gap-1.5 text-xs text-green-700"><i
                                            data-lucide="check-circle-2" class="h-3.5 w-3.5"></i>Stok tersedia:
                                    {{ $product->stock }}</p>@else<p class="text-xs font-medium text-red-600">Stok
                                        habis</p>
                                @endif
                            </div>
                            <div
                                class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4 sm:w-52 sm:shrink-0 sm:flex-col sm:items-end sm:border-0 sm:pt-0">
                                <form action="{{ route('cart.update', $item) }}" method="POST"
                                    class="flex items-center gap-2">
                                    @csrf @method('PATCH')
                                    <label for="quantity-{{ $item->id }}"
                                        class="text-xs font-medium text-gray-500">Qty</label>
                                    <input id="quantity-{{ $item->id }}" name="quantity" type="number"
                                        value="{{ $item->quantity }}" min="1"
                                        max="{{ max(1, $product->stock) }}" required inputmode="numeric"
                                        class="h-9 w-16 rounded-lg border border-gray-300 bg-white px-2 text-center text-sm font-medium text-gray-900 outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"
                                        aria-label="Jumlah {{ $product->name }}">
                                    <button type="submit"
                                        class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-xs font-semibold text-gray-700 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">Perbarui</button>
                                </form>
                                <div class="flex w-full items-center justify-between gap-3 sm:justify-end">
                                    <p class="text-sm font-bold text-gray-900">Rp
                                        {{ number_format($product->price * $item->quantity, 0, ',', '.') }}</p>
                                    <form action="{{ route('cart.destroy', $item) }}" method="POST"
                                        onsubmit="return confirm('Hapus {{ addslashes($product->name) }} dari keranjang?')">
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
                    class="inline-flex items-center gap-2 px-1 py-2 text-sm font-semibold text-indigo-600 transition hover:text-indigo-700"><i
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
                        <div class="flex justify-between gap-3 text-sm"><span class="text-gray-500">Subtotal</span><span
                                class="font-medium text-gray-900">Rp
                                {{ number_format($totalPrice, 0, ',', '.') }}</span></div>
                        <div class="space-y-2 border-t border-dashed border-gray-200 pt-4 text-sm">
                            <div class="flex justify-between gap-3"><span class="text-gray-500">Subtotal</span><span
                                    class="font-medium text-gray-900">Rp
                                    {{ number_format($totalPrice, 0, ',', '.') }}</span></div>
                            <div class="flex justify-between gap-3"><span class="text-gray-500">Ongkos
                                    kirim</span><span id="shipping-cost" class="font-medium text-gray-900">Belum
                                    dihitung</span></div>
                            <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-3"><span
                                    class="font-semibold text-gray-900">Total Pembayaran</span><span
                                    id="checkout-total" data-subtotal="{{ $totalPrice }}"
                                    class="text-lg font-bold text-gray-900">Rp
                                    {{ number_format($totalPrice, 0, ',', '.') }}</span></div>
                        </div>
                        <form id="checkout-form" action="{{ route('checkout.store') }}" method="POST"
                            class="space-y-3 pt-1">
                            @csrf
                            <div class="mb-1 flex items-center justify-between gap-2">
                                <label for="checkout-address" class="text-sm font-semibold text-gray-800">Alamat
                                    Pengiriman</label>
                                <a href="{{ route('addresses.index') }}"
                                    class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Kelola alamat</a>
                            </div>
                            @if ($addresses->isNotEmpty())
                                <select id="checkout-address" name="address_id" required
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                                    @foreach ($addresses as $address)
                                        <option value="{{ $address->id }}" @selected(old('address_id', $addresses->firstWhere('is_default', true)?->id ?? $addresses->first()->id) == $address->id)>
                                            {{ $address->label }}{{ $address->is_default ? ' (Utama)' : '' }} —
                                            {{ $address->recipient_name }} | {{ $address->formattedAddress() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('address_id')
                                    <p class="text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="text-xs leading-5 text-gray-500">Pastikan kode pos alamat sudah benar. <a
                                        href="{{ route('addresses.create') }}"
                                        class="font-semibold text-indigo-600 hover:text-indigo-700">Tambah alamat
                                        baru</a></p>
                            @else
                                <div
                                    class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs leading-5 text-amber-800">
                                    Untuk menghitung ongkir live, simpan alamat lengkap beserta kode pos terlebih
                                    dahulu. <a href="{{ route('addresses.create') }}"
                                        class="font-semibold underline">Tambah alamat</a>.</div>
                            @endif
                            <button id="calculate-shipping" type="button" @disabled($addresses->isEmpty())
                                class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100 disabled:cursor-not-allowed disabled:opacity-50">Hitung
                                Ongkos Kirim</button>
                            <p id="shipping-message" class="text-xs leading-5 text-gray-500" role="status">Pilih
                                alamat lalu hitung tarif kurir yang tersedia.</p>
                            <div id="shipping-options" class="space-y-2" aria-live="polite"></div>
                            <input type="hidden" name="shipping_quote_id" id="shipping-quote-id">
                            <input type="hidden" name="shipping_rate_key" id="shipping-rate-key">
                            @error('shipping_rate_key')
                                <p class="text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <button id="place-order" type="submit" disabled
                                class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">Buat
                                Pesanan<i data-lucide="arrow-right" class="h-4 w-4"></i></button>
                        </form>
                        <a href="{{ route('shop') }}"
                            class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Lanjut
                            Belanja</a>
                    </div>
                </div>
            </aside>
        </div>
        @push('scripts')
            <script>
                (() => {
                    const form = document.getElementById('checkout-form');
                    if (!form) return;
                    const address = document.getElementById('checkout-address');
                    const calculate = document.getElementById('calculate-shipping');
                    const options = document.getElementById('shipping-options');
                    const message = document.getElementById('shipping-message');
                    const quoteInput = document.getElementById('shipping-quote-id');
                    const rateInput = document.getElementById('shipping-rate-key');
                    const submit = document.getElementById('place-order');
                    const shippingCost = document.getElementById('shipping-cost');
                    const total = document.getElementById('checkout-total');
                    const subtotal = Number(total.dataset.subtotal);
                    const currency = value => 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                    const clearSelection = () => {
                        quoteInput.value = '';
                        rateInput.value = '';
                        submit.disabled = true;
                        shippingCost.textContent = 'Belum dihitung';
                        total.textContent = currency(subtotal);
                    };
                    if (address) address.addEventListener('change', () => {
                        clearSelection();
                        options.replaceChildren();
                        message.textContent = 'Alamat berubah. Hitung ulang ongkos kirim.';
                    });
                    if (calculate) calculate.addEventListener('click', async () => {
                        clearSelection();
                        options.replaceChildren();
                        calculate.disabled = true;
                        calculate.textContent = 'Menghitung...';
                        message.textContent = 'Mengambil tarif kurir...';
                        try {
                            const response = await fetch(@json(route('checkout.shipping-rates')), {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': form.querySelector('[name=_token]').value
                                },
                                body: JSON.stringify({
                                    address_id: address.value
                                })
                            });
                            const data = await response.json();
                            if (!response.ok) throw new Error(data.message || 'Tarif pengiriman tidak tersedia.');
                            quoteInput.value = data.quote_id;
                            const courierGroups = new Map();
                            data.rates.forEach(rate => {
                                const key = rate.courier_code;
                                if (!courierGroups.has(key)) {
                                    courierGroups.set(key, {
                                        name: rate.courier_name,
                                        rates: [],
                                    });
                                }
                                courierGroups.get(key).rates.push(rate);
                            });

                            courierGroups.forEach(group => {
                                const section = document.createElement('section');
                                section.className = 'space-y-2';

                                const heading = document.createElement('div');
                                heading.className =
                                    'flex items-center justify-between gap-2 rounded-lg bg-gray-50 px-3 py-2';
                                const courierName = document.createElement('h3');
                                courierName.className = 'text-sm font-bold text-gray-800';
                                courierName.textContent = group.name;
                                const serviceCount = document.createElement('span');
                                serviceCount.className = 'text-xs text-gray-500';
                                serviceCount.textContent = group.rates.length + ' layanan';
                                heading.append(courierName, serviceCount);
                                section.append(heading);

                                group.rates.forEach(rate => {
                                    const label = document.createElement('label');
                                    label.className =
                                        'flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 text-sm transition hover:border-indigo-300 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50';
                                    const radio = document.createElement('input');
                                    radio.type = 'radio';
                                    radio.name = 'shipping-choice';
                                    radio.value = rate.rate_key;
                                    radio.required = true;
                                    radio.className = 'mt-1 accent-indigo-600';
                                    radio.addEventListener('change', () => {
                                        rateInput.value = rate.rate_key;
                                        shippingCost.textContent = currency(rate.price);
                                        total.textContent = currency(subtotal + Number(rate.price));
                                        submit.disabled = false;
                                    });
                                    const detail = document.createElement('span');
                                    detail.className = 'min-w-0 flex-1';
                                    const title = document.createElement('span');
                                    title.className = 'block font-semibold text-gray-900';
                                    title.textContent = rate.service_name;
                                    const etd = document.createElement('span');
                                    etd.className = 'block text-xs text-gray-500';
                                    etd.textContent = rate.duration ? 'Estimasi: ' + rate.duration :
                                        'Estimasi mengikuti kurir';
                                    const price = document.createElement('span');
                                    price.className = 'whitespace-nowrap font-semibold text-gray-900';
                                    price.textContent = currency(rate.price);
                                    detail.append(title, etd);
                                    label.append(radio, detail, price);
                                    section.append(label);
                                });

                                options.append(section);
                            });
                            message.textContent = data.rates.length +
                                ' layanan tersedia. Pilih salah satu layanan untuk melanjutkan.';
                        } catch (error) {
                            message.textContent = error.message;
                        } finally {
                            calculate.disabled = false;
                            calculate.textContent = 'Hitung Ulang Ongkos Kirim';
                        }
                    });
                    form.addEventListener('submit', event => {
                        if (!quoteInput.value || !rateInput.value) {
                            event.preventDefault();
                            message.textContent = 'Hitung ongkir dan pilih layanan pengiriman terlebih dahulu.';
                        }
                    });
                })();
            </script>
        @endpush
    @endif
</x-layout-component>
