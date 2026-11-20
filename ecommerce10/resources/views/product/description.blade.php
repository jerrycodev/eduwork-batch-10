<x-storefront-layout>
    @php
        $variantData = $product->variants->where('is_active', true)->map(fn ($variant) => [
            'id' => (string) $variant->id,
            'price' => (int) $variant->price,
            'stock' => (int) $variant->stock,
            'option_values' => $variant->optionValues->mapWithKeys(fn ($value) => [(string) $value->option->code => (string) $value->id]),
            'image' => $variant->images->first()?->path,
        ])->values();
        $initialVariantId = $product->variants->firstWhere('is_default', true)?->id ?? $product->variants->first()?->id;
    @endphp
    <x-breadcrumb :links="$links" />

    @if (session('error'))
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
            role="alert"><i data-lucide="alert-circle"
                class="mt-0.5 h-4 w-4 shrink-0"></i><span>{{ session('error') }}</span></div>
    @endif

    <div class="container grid grid-cols-1 gap-6 sm:gap-8 md:grid-cols-2 lg:gap-12">

        <!-- Kiri: Galeri Gambar -->
        <div class="flex flex-col gap-4" x-data="{ activeImage: @js($product->main_image) }" @product-image-change.window="activeImage = $event.detail.image">
            <!-- Gambar Utama -->
            <div class="bg-muted/50 aspect-square overflow-hidden rounded-lg">
                <img id="product-main-image" src="{{ $product->main_image ?? 'https://placehold.co/600x600' }}"
                    alt="{{ $product->name ?? 'Gambar Produk' }}" class="h-full w-full object-cover object-center"
                    x-bind:src="activeImage ?? @js($product->main_image)">
            </div>

            <!-- Thumbnail gallery -->
            @if ($product->images->isNotEmpty())
                <div class="grid grid-cols-4 gap-2 sm:gap-4">
                    @foreach ($product->images as $image)
                        <button type="button" @click="activeImage = @js($image->path)"
                            class="aspect-square overflow-hidden rounded-md border-2 transition-colors"
                            :class="activeImage === @js($image->path) ? 'border-primary-600' :
                                'border-transparent hover:border-primary-300'"
                            aria-label="Pilih gambar {{ $loop->iteration }}">
                            <img src="{{ $image->path }}" alt="{{ $product->name }} - gambar {{ $loop->iteration }}"
                                class="h-full w-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Kanan: Detail Produk -->
        <div class="flex flex-col" @if ($product->options->isNotEmpty()) x-data="productVariantSelector(@js($variantData), @js($initialVariantId))" @endif>
            <h1 class="text-foreground mb-2 text-2xl font-bold tracking-tight sm:text-3xl lg:text-4xl">
                {{ $product->name ?? 'Nama Produk Berkualitas Tinggi' }}
            </h1>

            <!-- Harga & Badge -->
            <div class="mb-5 flex flex-wrap items-center gap-3 sm:mb-6 sm:gap-4">
                <span id="product-price" class="text-foreground text-xl font-bold sm:text-2xl" @if ($product->options->isNotEmpty()) x-text="priceLabel" @endif>
                    Rp {{ number_format($product->price ?? 250000, 0, ',', '.') }}
                </span>
            </div>

            <!-- Deskripsi Singkat -->
            <p class="text-muted-foreground mb-6 line-clamp-4 text-sm sm:mb-8 sm:text-base">
                {{ $product->description ?? 'Ini adalah deskripsi produk yang menjelaskan secara detail mengenai fitur, bahan, dan keunggulan produk ini. Dirancang menggunakan material premium untuk memastikan kenyamanan dan daya tahan jangka panjang.' }}
            </p>

            @if ($product->options->isNotEmpty())
                <div class="mb-6 space-y-4" data-product-variant-selector>
                    @foreach ($product->options as $option)
                        <fieldset>
                            <legend class="mb-2 text-sm font-semibold text-gray-900">
                                {{ $option->name }}:
                                <span data-selected-option-label="{{ $option->code }}" class="font-normal text-gray-600"></span>
                            </legend>
                            @if ($option->display_type === 'dropdown')
                                <select data-option-code="{{ $option->code }}" @change="selectOption('{{ $option->code }}', $event.target.value)" class="ui-input w-full max-w-xs rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm">
                                    @foreach ($option->values as $value)
                                        <option value="{{ $value->id }}" data-value-label="{{ $value->label }}">{{ $value->label }}</option>
                                    @endforeach
                                </select>
                            @else
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($option->values as $value)
                                        <button type="button" @click="selectOption('{{ $option->code }}', '{{ $value->id }}')"
                                            data-option-code="{{ $option->code }}"
                                            data-value-id="{{ $value->id }}"
                                            data-value-label="{{ $value->label }}"
                                            @if ($option->display_type === 'color' && $value->color_hex)
                                                style="background-color: {{ $value->color_hex }}"
                                            @elseif ($value->swatch_image)
                                                style="background-image: url('{{ \Illuminate\Support\Str::startsWith($value->swatch_image, ['http://', 'https://', '/']) ? $value->swatch_image : \Illuminate\Support\Facades\Storage::disk('public')->url($value->swatch_image) }}'); background-size: cover"
                                            @endif
                                            class="{{ $option->display_type === 'color' ? 'h-10 w-10 rounded-lg border-2 border-transparent shadow-sm' : 'rounded-lg border border-gray-300 px-3 py-2 text-sm' }} transition"
                                            aria-label="{{ $option->name }} {{ $value->label }}"
                                            title="{{ $value->label }}">
                                            @if ($option->display_type !== 'color' && ! $value->swatch_image)
                                                {{ $value->label }}
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </fieldset>
                    @endforeach
                </div>
            @endif

            <!-- Form Add to Cart -->
            <form id="product-cart-form" action="{{ route('cart.store', $product) }}" method="POST" class="mb-8"
                x-data="{ quantity: {{ max(1, min((int) old('quantity', 1), max((int) $product->stock, 1))) }}, min: 1, max: {{ max(1, (int) $product->stock) }} }"
                x-on:variant-stock-updated="max = Math.max(1, $event.detail.stock); quantity = Math.min(quantity, max)">
                @csrf
                <input type="hidden" name="product_variant_id" id="selected-product-variant" x-ref="variantInput" value="{{ $initialVariantId }}">

                <div class="flex flex-col gap-4">
                    <div class="flex flex-wrap items-center gap-5">
                        <label for="quantity" class="w-20 text-sm font-medium text-gray-900">Jumlah</label>
                        <div
                            class="focus-within:border-primary-500 focus-within:ring-primary-100 inline-flex h-11 items-center overflow-hidden rounded-lg border border-gray-300 bg-white shadow-sm focus-within:ring-2">
                            <button type="button" data-button-shape="preserve" @click="quantity = Math.max(min, Number(quantity || min) - 1)"
                                :disabled="quantity <= min"
                                class="hover:text-primary-600 inline-flex h-full w-10 items-center justify-center text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                                aria-label="Kurangi jumlah">
                                <i data-lucide="minus" class="h-4 w-4" aria-hidden="true"></i>
                            </button>
                            <input type="number" id="quantity" name="quantity" x-model.number="quantity"
                                min="1" max="{{ $product->stock }}" step="1" required inputmode="numeric"
                                class="h-full w-12 border-0 bg-transparent p-0 text-center text-sm font-medium tabular-nums text-gray-900 focus:ring-0"
                                aria-label="Jumlah produk">
                            <button type="button" data-button-shape="preserve" @click="quantity = Math.min(max, Number(quantity || min) + 1)"
                                :disabled="quantity >= max"
                                class="hover:text-primary-600 inline-flex h-full w-10 items-center justify-center text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                                aria-label="Tambah jumlah">
                                <i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>
                            </button>
                        </div>
                        <span id="product-stock" class="text-xs text-gray-500" @if ($product->options->isNotEmpty()) x-text="stockLabel" @endif>Stok tersedia: {{ $product->stock }}</span>
                    </div>
                    @error('quantity')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <hr class="my-2 border-gray-200">

                    <div class="flex items-stretch gap-3">
                        <x-ui.button type="submit" size="lg" class="flex-1" x-bind:disabled="@js($product->stock < 1)">
                            <i data-lucide="shopping-cart" class="h-5 w-5" aria-hidden="true"></i>
                            <span>{{ $product->stock > 0 ? 'Tambah ke Keranjang' : 'Stok Habis' }}</span>
                        </x-ui.button>
                        @auth
                            @unlessadmin
                                <button type="submit" formmethod="POST" formaction="{{ route('wishlist.toggle', $product) }}"
                                    class="hover:border-primary-300 hover:bg-primary-50 hover:text-primary-600 focus:ring-primary-500 {{ $isWishlisted ? 'border-rose-200 bg-rose-50 text-rose-600' : 'bg-white text-gray-700' }} inline-flex min-h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-gray-300 transition focus:outline-none focus:ring-2 focus:ring-offset-2"
                                    aria-label="{{ $isWishlisted ? 'Hapus dari wishlist' : 'Tambahkan ke wishlist' }}"
                                    title="{{ $isWishlisted ? 'Hapus dari wishlist' : 'Tambahkan ke wishlist' }}">
                                    <i data-lucide="heart" class="{{ $isWishlisted ? 'fill-current' : '' }} h-5 w-5"
                                        aria-hidden="true"></i>
                                </button>
                            @endunlessadmin
                        @else
                            <a href="{{ route('login', ['redirect_to' => route('product-detail', $product->slug, absolute: false)]) }}"
                                class="hover:border-primary-300 hover:bg-primary-50 hover:text-primary-600 focus:ring-primary-500 inline-flex min-h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 transition focus:outline-none focus:ring-2 focus:ring-offset-2"
                                aria-label="Login untuk menambahkan ke wishlist"
                                title="Login untuk menambahkan ke wishlist">
                                <i data-lucide="heart" class="h-5 w-5" aria-hidden="true"></i>
                            </a>
                        @endauth
                    </div>
                </div>
            </form>

            <!-- Informasi Tambahan (Spesifikasi, Pengiriman) -->
            <div class="bg-card rounded-lg border border-neutral-200">
                <div class="border-b border-neutral-200 p-4 sm:p-5">
                    <h3 class="flex items-center gap-2 font-medium">
                        <i data-lucide="truck" class="text-muted-foreground h-4 w-4"></i>
                        Informasi Pengiriman
                    </h3>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Ongkos kirim dan estimasi tiba menyesuaikan alamat tujuan serta layanan pengiriman yang tersedia.
                        Rincian dapat dilihat saat checkout.
                    </p>
                </div>
                <div class="p-4 sm:p-5">
                    <h3 class="flex items-center gap-2 font-medium">
                        <i data-lucide="shield-check" class="text-muted-foreground h-4 w-4"></i>
                        Pembayaran Aman
                    </h3>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Pembayaran dilakukan melalui proses yang aman. Anda dapat memantau status pembayaran dan pesanan
                        melalui halaman pesanan.
                    </p>
                </div>
            </div>

        </div>
    </div>

    @if ($relatedProducts->isNotEmpty())
        <section id="related-products" class="mt-12 border-t border-gray-200 pt-8 container">
            <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Produk Terkait</h2>
                    <p class="mt-1 text-sm text-gray-500">Produk lain dari kategori {{ $product->category->name }} yang
                        mungkin Anda sukai.</p>
                </div>
                <a href="{{ route('shop', ['category' => $product->category->slug]) }}"
                    class="text-primary-600 hover:text-primary-700 inline-flex items-center gap-1 text-sm font-semibold transition">
                    Lihat semua <i data-lucide="arrow-right" class="h-4 w-4" aria-hidden="true"></i>
                </a>
            </div>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($relatedProducts as $relatedProduct)
                    <article
                        class="hover:border-primary-200 group overflow-hidden rounded-xl border border-gray-200 bg-white transition hover:-translate-y-0.5 hover:shadow-md">
                        <a href="{{ route('product-detail', $relatedProduct->slug) }}" class="block">
                            <div class="aspect-square overflow-hidden bg-gray-50">
                                <img src="{{ $relatedProduct->image ?: 'https://placehold.co/600x600' }}"
                                    alt="{{ $relatedProduct->name }}" loading="lazy"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            </div>
                            <div class="p-3 sm:p-4">
                                <h3
                                    class="group-hover:text-primary-600 line-clamp-2 min-h-10 text-sm font-semibold text-gray-900 transition sm:text-base">
                                    {{ $relatedProduct->name }}</h3>
                                <p class="mt-2 text-sm font-bold text-gray-900">Rp
                                    {{ number_format($relatedProduct->price, 0, ',', '.') }}</p>
                                <div
                                    class="mt-2 flex flex-wrap items-center justify-between gap-1 text-xs text-gray-500">
                                    <span class="text-amber-600">★
                                        {{ $relatedProduct->reviews_count > 0 ? number_format($relatedProduct->average_rating, 1) : '—' }}
                                        <span
                                            class="text-gray-400">({{ $relatedProduct->reviews_count }})</span></span>
                                    <span>{{ $relatedProduct->stock }} tersedia</span>
                                </div>
                            </div>
                        </a>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <section id="reviews" class="mt-12 border-t border-gray-200 pt-8 container">
        <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_340px]">
            <div>
                <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Ulasan Pelanggan</h2>
                        <p class="mt-1 text-sm text-gray-500">Ulasan yang ditampilkan telah ditinjau oleh admin.</p>
                    </div>
                    <div class="text-right">
                        @if ($product->reviews_count > 0)
                            <p class="text-2xl font-bold text-gray-900">
                                {{ number_format($product->average_rating, 1) }} <span class="text-amber-500">★</span>
                            </p>
                            <p class="text-xs text-gray-500">{{ $product->reviews_count }} ulasan</p>
                        @else
                            <p class="text-sm text-gray-500">Belum ada rating</p>
                        @endif
                    </div>
                </div>

                <div class="space-y-4">
                    @forelse ($reviews as $review)
                        <article class="rounded-xl border border-gray-200 bg-white p-4 sm:p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="font-semibold text-gray-900">{{ $review->title ?: 'Ulasan produk' }}
                                    </h3>
                                    <p class="mt-1 text-xs text-gray-500">{{ $review->user->name }} ·
                                        {{ $review->created_at->translatedFormat('d M Y') }}</p>
                                </div>
                                <span class="shrink-0 text-sm font-semibold text-amber-600">★
                                    {{ $review->rating }}/5</span>
                            </div>
                            <p class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-600">{{ $review->comment }}
                            </p>
                            @if ($review->media->isNotEmpty())
                                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                                    @foreach ($review->media as $media)
                                        @if ($media->media_type === 'image')
                                            <a href="{{ Storage::disk('public')->url($media->path) }}"
                                                target="_blank" rel="noopener"
                                                class="block overflow-hidden rounded-lg border border-gray-200">
                                                <img src="{{ Storage::disk('public')->url($media->path) }}"
                                                    alt="Foto ulasan {{ $loop->iteration }}" loading="lazy"
                                                    class="aspect-square h-full w-full object-cover">
                                            </a>
                                        @else
                                            <video controls preload="metadata"
                                                class="aspect-square w-full rounded-lg border border-gray-200 bg-black object-contain">
                                                <source src="{{ Storage::disk('public')->url($media->path) }}"
                                                    type="{{ $media->mime_type }}">
                                                Browser Anda tidak mendukung video.
                                            </video>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </article>
                    @empty
                        <div
                            class="rounded-xl border border-dashed border-gray-300 px-5 py-10 text-center text-sm text-gray-500">
                            Belum ada ulasan yang ditampilkan untuk produk ini.</div>
                    @endforelse
                </div>
                @if ($reviews->hasPages())
                    <div class="mt-5">{{ $reviews->links() }}</div>
                @endif
            </div>

            <aside class="h-fit rounded-xl border border-gray-200 bg-white p-5">
                <h3 class="font-semibold text-gray-900">Tulis Ulasan</h3>
                <p class="mt-1 text-sm text-gray-500">Bagikan pengalaman Anda menggunakan produk ini.</p>
                @if (session('review_success'))
                    <div class="mt-4 rounded-lg bg-green-50 p-3 text-sm text-green-700">
                        {{ session('review_success') }}</div>
                @endif
                @auth
                    @if (Auth::user()->role === 'admin')
                        <p class="mt-4 rounded-lg bg-gray-50 p-3 text-sm text-gray-500">Akun admin tidak dapat mengirim
                            ulasan pelanggan.</p>
                    @elseif (!Auth::user()->orders()->where('payment_status', 'paid')->where('status', 'completed')->whereHas('orderItems', fn($query) => $query->where('product_id', $product->id))->exists())
                        <p class="mt-4 rounded-lg bg-gray-50 p-3 text-sm text-gray-600">Anda dapat memberikan ulasan
                            setelah pesanan selesai dan pembayaran terverifikasi.</p>
                    @elseif (Auth::user()->reviews()->where('product_id', $product->id)->exists())
                        <p class="mt-4 rounded-lg bg-gray-50 p-3 text-sm text-gray-600">Anda sudah mengirim ulasan untuk
                            produk ini. Ulasan akan ditampilkan setelah disetujui admin.</p>
                    @else
                        <form action="{{ route('product-reviews.store', $product) }}" method="POST"
                            enctype="multipart/form-data" class="mt-4 space-y-3">
                            @csrf
                            <label class="block text-sm font-medium text-gray-700">Rating
                                <select name="rating" required
                                    class="focus:border-primary-500 focus:ring-primary-500 mt-1 w-full rounded-lg border-gray-300 text-sm">
                                    <option value="5" @selected(old('rating') == 5)>5 - Sangat puas</option>
                                    <option value="4" @selected(old('rating') == 4)>4 - Puas</option>
                                    <option value="3" @selected(old('rating') == 3)>3 - Cukup</option>
                                    <option value="2" @selected(old('rating') == 2)>2 - Kurang puas</option>
                                    <option value="1" @selected(old('rating') == 1)>1 - Tidak puas</option>
                                </select>
                            </label>
                            <label class="block text-sm font-medium text-gray-700">Judul (opsional)<input name="title"
                                    value="{{ old('title') }}" maxlength="120"
                                    class="focus:border-primary-500 focus:ring-primary-500 mt-1 w-full rounded-lg border-gray-300 text-sm"
                                    placeholder="Ringkasan ulasan"></label>
                            <label class="block text-sm font-medium text-gray-700">Foto / Video (maks. 5 file, 30 MB per
                                file)
                                <input type="file" name="media[]" multiple
                                    accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/webm"
                                    class="file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:px-3 file:py-2 file:text-xs file:font-semibold">
                            </label>
                            @error('media')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            @foreach ($errors->get('media.*') as $messages)
                                @foreach ($messages as $message)
                                    <p class="text-xs text-red-600">{{ $message }}</p>
                                @endforeach
                            @endforeach
                            <label class="block text-sm font-medium text-gray-700">Ulasan
                                <textarea name="comment" rows="4" required minlength="5" maxlength="3000"
                                    class="focus:border-primary-500 focus:ring-primary-500 mt-1 w-full rounded-lg border-gray-300 text-sm"
                                    placeholder="Ceritakan pengalaman Anda...">{{ old('comment') }}</textarea>
                            </label>
                            @error('rating')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            @error('title')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            @error('comment')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            @error('review')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <button type="submit"
                                class="bg-primary-600 hover:bg-primary-700 w-full rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition">Kirim
                                Ulasan</button>
                            <p class="text-xs leading-5 text-gray-400">Hanya pembeli yang sudah membayar dapat mengulas.
                                Maksimal 5 foto/video. Admin akan meninjau sebelum dipublikasikan.</p>
                        </form>
                    @endif
                @else
                    <a href="{{ route('login') }}"
                        class="bg-primary-600 hover:bg-primary-700 mt-4 inline-flex w-full justify-center rounded-lg px-4 py-2.5 text-sm font-semibold text-white">Login
                        untuk Menulis Ulasan</a>
                @endauth
            </aside>
        </div>
    </section>


</x-storefront-layout>
