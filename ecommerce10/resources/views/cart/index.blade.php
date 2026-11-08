<x-layout-component>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 sm:mb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">Keranjang Belanja</h1>
            <p class="mt-1 text-sm text-muted-foreground">Periksa kembali produk sebelum melanjutkan pesanan.</p>
        </div>
        <span class="badge" data-variant="secondary">
            {{ $totalQuantity }} Barang
        </span>
    </div>

    @if (session('success'))
        <div class="mb-5 rounded-lg border border-green-500/30 bg-green-500/10 px-4 py-3 text-sm text-green-700" role="status">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-5 rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive" role="alert">
            {{ session('error') }}
        </div>
    @endif

    @if ($cartItems->isEmpty())
        <div class="card flex flex-col items-center px-5 py-12 text-center sm:py-16">
            <div class="mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-muted">
                <i data-lucide="shopping-cart" class="h-9 w-9 text-muted-foreground"></i>
            </div>
            <h2 class="text-lg font-semibold text-foreground">Keranjang belanja masih kosong</h2>
            <p class="mt-2 max-w-md text-sm text-muted-foreground">
                @auth
                    Yuk, temukan produk yang kamu butuhkan dan tambahkan ke keranjang.
                @else
                    Masuk ke akunmu untuk melihat keranjang, atau mulai jelajahi produk yang tersedia.
                @endauth
            </p>
            <a href="{{ route('shop') }}" class="btn mt-6" data-variant="default">
                Mulai Belanja
                <i data-lucide="arrow-right" class="ml-2 h-4 w-4"></i>
            </a>
            @guest
                <a href="{{ route('login') }}" class="mt-3 text-sm font-medium text-primary hover:underline">Masuk ke akun</a>
            @endguest
        </div>
    @else
        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12 lg:gap-8">
            <section class="flex min-w-0 flex-col gap-4 lg:col-span-8" aria-label="Daftar produk di keranjang">
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

                    <article class="card flex min-w-0 flex-col gap-4 p-4 sm:flex-row sm:items-center sm:gap-5 sm:p-5">
                        <a href="{{ route('product-detail', $product) }}"
                            class="h-24 w-24 shrink-0 self-start overflow-hidden rounded-lg bg-muted/50 sm:h-28 sm:w-28">
                            <img src="{{ $imageUrl }}" alt="{{ $product->name }}"
                                class="h-full w-full object-cover" loading="lazy">
                        </a>

                        <div class="flex min-w-0 flex-1 flex-col justify-between gap-3">
                            <div class="min-w-0">
                                @if ($product->category)
                                    <p class="mb-1 text-xs text-muted-foreground">{{ $product->category->name }}</p>
                                @endif
                                <a href="{{ route('product-detail', $product) }}"
                                    class="line-clamp-2 font-semibold text-foreground hover:text-primary">
                                    {{ $product->name }}
                                </a>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    @if ($product->stock > 0)
                                        Stok tersedia: {{ $product->stock }}
                                    @else
                                        <span class="text-destructive">Stok habis</span>
                                    @endif
                                </p>
                            </div>
                            <p class="font-semibold text-foreground">
                                Rp {{ number_format($product->price, 0, ',', '.') }}
                                <span class="font-normal text-muted-foreground">/ item</span>
                            </p>
                        </div>

                        <div class="flex w-full items-end justify-between gap-4 border-t pt-4 sm:w-auto sm:min-w-36 sm:flex-col sm:items-end sm:border-0 sm:pt-0">
                            <form action="{{ route('cart.update', $item) }}" method="POST" class="flex items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <label for="quantity-{{ $item->id }}" class="sr-only">Jumlah {{ $product->name }}</label>
                                <input id="quantity-{{ $item->id }}" name="quantity" type="number"
                                    value="{{ $item->quantity }}" min="1" max="{{ $product->stock }}"
                                    required class="input h-9 w-20 px-2 text-center" aria-label="Jumlah produk">
                                <button type="submit" class="btn h-9 px-3" data-variant="outline">
                                    Perbarui
                                </button>
                            </form>

                            <div class="flex items-center gap-3 sm:justify-end">
                                <p class="text-sm font-semibold text-foreground">
                                    Rp {{ number_format($product->price * $item->quantity, 0, ',', '.') }}
                                </p>
                                <form action="{{ route('cart.destroy', $item) }}" method="POST"
                                    onsubmit="return confirm('Hapus {{ addslashes($product->name) }} dari keranjang?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn h-9 w-9 text-destructive hover:bg-destructive/10"
                                        data-variant="ghost" data-size="icon" aria-label="Hapus {{ $product->name }}">
                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>

            <aside class="min-w-0 lg:col-span-4">
                <div class="card lg:sticky lg:top-24">
                    <div class="card-header border-b">
                        <h2 class="card-title text-lg">Ringkasan Belanja</h2>
                    </div>
                    <div class="card-content flex flex-col gap-4 py-5 sm:py-6">
                        <div class="flex justify-between gap-3 text-sm">
                            <span class="text-muted-foreground">Total barang</span>
                            <span class="font-medium text-foreground">{{ $totalQuantity }} item</span>
                        </div>
                        <div class="flex justify-between gap-3 text-sm">
                            <span class="text-muted-foreground">Subtotal</span>
                            <span class="font-medium text-foreground">Rp {{ number_format($totalPrice, 0, ',', '.') }}</span>
                        </div>
                        <hr class="my-1 border-muted">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-bold text-foreground">Total Harga</span>
                            <span class="text-lg font-bold text-foreground">Rp {{ number_format($totalPrice, 0, ',', '.') }}</span>
                        </div>
                        <p class="text-xs text-muted-foreground">Ongkos kirim dan biaya lainnya akan dihitung pada tahap berikutnya.</p>
                    </div>
                    <div class="card-footer flex flex-col gap-3 pt-0">
                        @auth
                            <form action="{{ route('checkout.store') }}" method="POST" class="w-full">
                                @csrf
                                <button type="submit" class="btn w-full" data-variant="default">
                                    Checkout Sekarang
                                    <i data-lucide="arrow-right" class="ml-2 h-4 w-4"></i>
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login', ['redirect_to' => route('cart', absolute: false)]) }}" class="btn w-full" data-variant="default">
                                Masuk untuk Checkout
                                <i data-lucide="arrow-right" class="ml-2 h-4 w-4"></i>
                            </a>
                        @endauth
                        <a href="{{ route('shop') }}" class="btn w-full" data-variant="outline">
                            Lanjut Belanja
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    @endif
</x-layout-component>
