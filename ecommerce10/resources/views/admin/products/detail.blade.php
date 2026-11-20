{{-- Dokumentasi: Detail lengkap katalog produk, relasi varian, SEO, media, inventori, dan aktivitas. --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold tracking-tight text-gray-900">Detail produk</h2>
                <p class="mt-1 text-sm text-gray-500">Informasi lengkap produk, varian, SEO, media, dan relasi katalog.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50"><i data-lucide="arrow-left" class="h-4 w-4"></i>Kembali</a>
                <a href="{{ route('products.edit', $product) }}" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700"><i data-lucide="pencil" class="h-4 w-4"></i>Edit produk</a>
            </div>
        </div>
    </x-slot>

    @php
        $formatMoney = fn ($value) => $value === null ? '—' : 'Rp '.number_format((float) $value, 0, ',', '.');
        $formatDimension = fn ($value) => $value === null ? '—' : rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',').' cm';
        $formatWeight = fn ($value) => $value === null ? '—' : number_format((int) $value, 0, ',', '.').' gram';
        $formatDate = fn ($value) => $value?->format('d M Y, H:i') ?? '—';
    @endphp

    <div class="space-y-6 py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            {{-- Ringkasan dan identitas produk. --}}
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="flex flex-col gap-4 border-b border-gray-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-lg font-bold text-gray-900">{{ $product->name }}</h1>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $product->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            @if ($product->is_featured)<span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Produk unggulan</span>@endif
                        </div>
                        <p class="mt-1 break-all text-xs text-gray-500">ID: {{ $product->id }}</p>
                    </div>
                    <a href="{{ route('product-detail', $product->slug) }}" target="_blank" rel="noopener" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-gray-300 px-3.5 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"><i data-lucide="external-link" class="h-4 w-4"></i>Lihat di storefront</a>
                </div>

                <div class="grid gap-6 p-5 md:grid-cols-[260px_minmax(0,1fr)] md:p-6">
                    <div class="space-y-3">
                        <div class="aspect-square overflow-hidden rounded-xl border border-gray-200 bg-gray-50">
                            @if ($product->main_image)
                                <img src="{{ $product->main_image }}" alt="{{ $product->name }}" class="h-full w-full object-contain">
                            @else
                                <div class="flex h-full items-center justify-center text-gray-300"><i data-lucide="image-off" class="h-12 w-12"></i></div>
                            @endif
                        </div>
                        <p class="text-center text-xs text-gray-500">Gambar utama produk</p>
                    </div>

                    <div class="min-w-0 space-y-5">
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            @foreach ([['Varian', $product->variants_count], ['Opsi', $product->options_count], ['Gambar', $product->images_count], ['Ulasan', $product->reviews_count], ['Di keranjang', $product->carts_count], ['Item pesanan', $product->order_items_count], ['Wishlist', $product->wishlist_items_count], ['Ulasan disetujui', $product->approved_reviews_count]] as [$label, $value])
                                <div class="rounded-xl border border-gray-100 bg-gray-50 p-3"><p class="text-xs text-gray-500">{{ $label }}</p><p class="mt-1 text-xl font-bold tabular-nums text-gray-900">{{ number_format($value) }}</p></div>
                            @endforeach
                        </div>
                        <dl class="grid gap-x-6 sm:grid-cols-2">
                            <div class="border-b border-gray-100 py-3"><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Nama produk</dt><dd class="mt-1 break-words text-sm font-medium text-gray-900">{{ $product->name }}</dd></div>
                            <div class="border-b border-gray-100 py-3"><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Slug</dt><dd class="mt-1 break-all text-sm font-medium text-gray-900">{{ $product->slug }}</dd></div>
                            <div class="border-b border-gray-100 py-3"><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Kategori</dt><dd class="mt-1 text-sm font-medium text-gray-900">{{ $product->category?->name ?? '—' }} @if($product->category)<span class="text-xs font-normal text-gray-500">({{ $product->category->slug }})</span>@endif</dd></div>
                            <div class="border-b border-gray-100 py-3"><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">SKU produk</dt><dd class="mt-1 break-all font-mono text-sm font-medium text-gray-900">{{ $product->sku ?: '—' }}</dd></div>
                            <div class="border-b border-gray-100 py-3"><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Barcode</dt><dd class="mt-1 break-all font-mono text-sm font-medium text-gray-900">{{ $product->barcode ?: '—' }}</dd></div>
                            <div class="border-b border-gray-100 py-3"><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Harga ringkasan</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $formatMoney($product->price) }}</dd></div>
                            <div class="border-b border-gray-100 py-3"><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Stok ringkasan</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ number_format($product->stock) }} unit</dd></div>
                            <div class="border-b border-gray-100 py-3"><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Klik produk</dt><dd class="mt-1 text-sm font-medium text-gray-900">{{ number_format($product->click ?? 0) }}</dd></div>
                        </dl>
                    </div>
                </div>
            </section>

            {{-- Semua gambar produk umum, tidak termasuk gambar yang terikat ke varian. --}}
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-4 flex items-center justify-between"><div><h2 class="font-semibold text-gray-900">Galeri produk</h2><p class="mt-1 text-sm text-gray-500">Gambar yang terhubung langsung ke produk.</p></div><span class="text-xs font-medium text-gray-500">{{ $product->images->count() }} gambar</span></div>
                @if ($product->images->isNotEmpty())
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-5">
                        @foreach ($product->images as $image)
                            <div class="overflow-hidden rounded-xl border border-gray-200">
                                <div class="aspect-square bg-gray-50"><img src="{{ $image->path }}" alt="Gambar {{ $loop->iteration }} {{ $product->name }}" class="h-full w-full object-contain"></div>
                                <div class="flex items-center justify-between gap-2 px-3 py-2"><span class="text-xs text-gray-500">Urutan {{ $image->sort_order }}</span>@if($image->is_primary)<span class="rounded-full bg-primary-50 px-2 py-0.5 text-[10px] font-semibold text-primary-700">Utama</span>@endif</div>
                                <p class="truncate border-t border-gray-100 px-3 py-2 font-mono text-[10px] text-gray-400" title="{{ $image->path }}">{{ $image->path }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="rounded-xl bg-gray-50 px-4 py-6 text-center text-sm text-gray-500">Belum ada gambar galeri produk.</p>
                @endif
                @if ($product->image)<p class="mt-3 break-all text-xs text-gray-500">Legacy image field: <span class="font-mono">{{ $product->image }}</span></p>@endif
            </section>

            {{-- Deskripsi, harga, dan atribut fisik di level produk. Harga/stok jual per varian ditampilkan di bawah. --}}
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="mb-4 font-semibold text-gray-900">Informasi produk & logistik</h2>
                <dl class="grid gap-x-8 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([['Harga produk', $formatMoney($product->price)], ['Harga pembanding', $formatMoney($product->compare_at_price)], ['Harga modal', $formatMoney($product->cost_price)], ['Berat', $formatWeight($product->weight_gram)], ['Panjang', $formatDimension($product->length_cm)], ['Lebar', $formatDimension($product->width_cm)], ['Tinggi', $formatDimension($product->height_cm)]] as [$label, $value])
                        <div class="border-b border-gray-100 py-3"><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $label }}</dt><dd class="mt-1 text-sm font-medium text-gray-900">{{ $value }}</dd></div>
                    @endforeach
                    <div class="border-b border-gray-100 py-3 sm:col-span-2 lg:col-span-3"><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Deskripsi</dt><dd class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-700">{{ $product->description ?: '—' }}</dd></div>
                </dl>
            </section>

            {{-- Konfigurasi opsi, termasuk swatch warna berbasis HEX dan gambar swatch. --}}
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-4 flex items-center justify-between"><div><h2 class="font-semibold text-gray-900">Opsi & nilai opsi</h2><p class="mt-1 text-sm text-gray-500">Relasi product_options dan product_option_values.</p></div><span class="text-xs text-gray-500">{{ $product->options->count() }} opsi</span></div>
                @forelse ($product->options as $option)
                    <div class="mb-4 last:mb-0 rounded-xl border border-gray-200 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2"><div><h3 class="font-semibold text-gray-900">{{ $option->name }}</h3><p class="mt-0.5 text-xs text-gray-500">Code: <span class="font-mono">{{ $option->code }}</span> · Display: {{ $option->display_type }} · Urutan: {{ $option->sort_order }}</p></div><span class="text-xs text-gray-500">{{ $option->values->count() }} nilai</span></div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($option->values as $value)
                                <div class="flex min-w-36 items-center gap-2 rounded-lg border border-gray-100 bg-gray-50 p-2">
                                    @if ($value->color_hex)<span class="h-8 w-8 shrink-0 rounded-md border border-black/10" style="background-color: {{ $value->color_hex }}"></span>@elseif($value->swatch_image)<img src="{{ $value->swatch_image }}" alt="{{ $value->label }}" class="h-8 w-8 shrink-0 rounded-md border border-gray-200 object-cover">@else<span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-white text-gray-400"><i data-lucide="tag" class="h-4 w-4"></i></span>@endif
                                    <div class="min-w-0"><p class="truncate text-xs font-semibold text-gray-800">{{ $value->label }}</p><p class="truncate font-mono text-[10px] text-gray-500">{{ $value->value }}</p>@if($value->color_hex)<p class="font-mono text-[10px] text-gray-400">{{ $value->color_hex }}</p>@endif</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="rounded-xl bg-gray-50 px-4 py-6 text-center text-sm text-gray-500">Produk ini tidak memiliki opsi. Varian menggunakan konfigurasi default.</p>
                @endforelse
            </section>

            {{-- Seluruh field varian, kombinasi option value, dan gambar masing-masing varian. --}}
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4 sm:px-6"><div class="flex flex-wrap items-center justify-between gap-2"><div><h2 class="font-semibold text-gray-900">Varian & inventori</h2><p class="mt-1 text-sm text-gray-500">SKU, barcode, harga, modal, stok, berat, dimensi, status, dan media per varian.</p></div><span class="text-xs text-gray-500">{{ $product->variants->count() }} varian</span></div></div>
                @forelse ($product->variants as $variant)
                    <article class="border-b border-gray-100 p-5 last:border-b-0 sm:p-6">
                        <div class="mb-4 flex flex-wrap items-center gap-2"><h3 class="font-semibold text-gray-900">{{ $variant->name ?: 'Varian '.$loop->iteration }}</h3>@if($variant->is_default)<span class="rounded-full bg-primary-50 px-2 py-1 text-[10px] font-semibold text-primary-700">Default</span>@endif<span class="rounded-full px-2 py-1 text-[10px] font-semibold {{ $variant->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">{{ $variant->is_active ? 'Aktif' : 'Nonaktif' }}</span></div>
                        <dl class="grid gap-x-5 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ([['ID varian', $variant->id], ['SKU', $variant->sku], ['Barcode', $variant->barcode ?: '—'], ['Harga jual', $formatMoney($variant->price)], ['Harga pembanding', $formatMoney($variant->compare_at_price)], ['Harga modal', $formatMoney($variant->cost_price)], ['Stok', number_format($variant->stock).' unit'], ['Berat', $formatWeight($variant->weight_gram)], ['Panjang', $formatDimension($variant->length_cm)], ['Lebar', $formatDimension($variant->width_cm)], ['Tinggi', $formatDimension($variant->height_cm)], ['Dibuat', $formatDate($variant->created_at)], ['Diperbarui', $formatDate($variant->updated_at)]] as [$label, $value])
                                <div class="border-b border-gray-100 py-2.5"><dt class="text-[10px] font-semibold uppercase tracking-wide text-gray-500">{{ $label }}</dt><dd class="mt-1 break-all text-xs font-medium text-gray-800">{{ $value }}</dd></div>
                            @endforeach
                            <div class="border-b border-gray-100 py-2.5 sm:col-span-2 lg:col-span-4"><dt class="text-[10px] font-semibold uppercase tracking-wide text-gray-500">Kombinasi opsi</dt><dd class="mt-1 flex flex-wrap gap-2">@forelse($variant->optionValues as $value)<span class="rounded-md bg-gray-100 px-2.5 py-1 text-xs text-gray-700">{{ $value->option?->name }}: {{ $value->label }} @if($value->color_hex)<span class="ml-1 font-mono text-gray-500">{{ $value->color_hex }}</span>@endif</span>@empty<span class="text-xs text-gray-500">Default / tanpa opsi</span>@endforelse</dd></div>
                        </dl>
                        <div class="mt-4"><p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Gambar varian ({{ $variant->images->count() }})</p>@if($variant->images->isNotEmpty())<div class="flex flex-wrap gap-3">@foreach($variant->images as $image)<div class="w-24 overflow-hidden rounded-lg border border-gray-200"><div class="aspect-square bg-gray-50"><img src="{{ $image->path }}" alt="{{ $variant->name }}" class="h-full w-full object-contain"></div><p class="truncate px-2 py-1 text-[10px] text-gray-500" title="{{ $image->path }}">{{ $image->path }}</p></div>@endforeach</div>@else<p class="text-xs text-gray-400">Tidak ada gambar khusus untuk varian ini.</p>@endif</div>
                    </article>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-gray-500">Belum ada varian produk.</p>
                @endforelse
            </section>

            {{-- Metadata SEO disajikan apa adanya, termasuk ketika belum diisi. --}}
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-4 flex items-center gap-2"><i data-lucide="search" class="h-5 w-5 text-primary-600"></i><div><h2 class="font-semibold text-gray-900">SEO & metadata</h2><p class="mt-1 text-sm text-gray-500">Metadata yang digunakan mesin pencari dan social preview storefront.</p></div></div>
                <dl class="space-y-4">
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Meta title</dt><dd class="mt-1 rounded-lg bg-gray-50 p-3 text-sm text-gray-800">{{ $product->meta_title ?: 'Belum diatur' }}</dd><p class="mt-1 text-right text-[10px] text-gray-400">{{ mb_strlen($product->meta_title ?? '') }} karakter</p></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Meta description</dt><dd class="mt-1 whitespace-pre-line rounded-lg bg-gray-50 p-3 text-sm leading-6 text-gray-800">{{ $product->meta_description ?: 'Belum diatur' }}</dd><p class="mt-1 text-right text-[10px] text-gray-400">{{ mb_strlen($product->meta_description ?? '') }} karakter</p></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Canonical storefront URL</dt><dd class="mt-1 break-all rounded-lg bg-gray-50 p-3 font-mono text-xs text-gray-700">{{ route('product-detail', $product->slug) }}</dd></div>
                </dl>
            </section>

            {{-- Ringkasan relasi dan aktivitas terbaru yang terkait dengan produk. --}}
            <section class="grid gap-6 lg:grid-cols-2">
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-5 py-4"><h2 class="font-semibold text-gray-900">Pergerakan stok terbaru</h2><p class="mt-1 text-sm text-gray-500">Maksimal 10 catatan terbaru.</p></div>
                    @forelse($product->stockMovements as $movement)
                        <div class="border-b border-gray-100 px-5 py-3 last:border-0"><div class="flex items-center justify-between gap-3"><span class="text-xs font-semibold text-gray-800">{{ str($movement->type)->replace('_', ' ')->title() }} · {{ $movement->variant?->sku ?: 'Produk' }}</span><span class="text-xs font-semibold tabular-nums {{ $movement->quantity < 0 ? 'text-red-600' : 'text-emerald-700' }}">{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</span></div><p class="mt-1 text-xs text-gray-500">{{ $movement->stock_before }} → {{ $movement->stock_after }} · {{ $movement->creator?->name ?? 'System' }} · {{ $formatDate($movement->created_at) }}</p>@if($movement->notes)<p class="mt-1 text-xs text-gray-600">{{ $movement->notes }}</p>@endif</div>
                    @empty
                        <p class="px-5 py-6 text-sm text-gray-500">Belum ada catatan pergerakan stok.</p>
                    @endforelse
                </div>

                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-5 py-4"><h2 class="font-semibold text-gray-900">Ulasan terbaru</h2><p class="mt-1 text-sm text-gray-500">Maksimal 5 ulasan terbaru.</p></div>
                    @forelse($product->reviews as $review)
                        <div class="border-b border-gray-100 px-5 py-3 last:border-0"><div class="flex flex-wrap items-center justify-between gap-2"><p class="text-xs font-semibold text-gray-800">{{ $review->user?->name ?? 'Pengguna' }}</p><span class="text-xs text-amber-600">{{ str_repeat('★', max(0, min(5, $review->rating))) }}{{ str_repeat('☆', 5 - max(0, min(5, $review->rating))) }}</span></div>@if($review->title)<p class="mt-1 text-xs font-semibold text-gray-700">{{ $review->title }}</p>@endif<p class="mt-1 whitespace-pre-line text-xs leading-5 text-gray-600">{{ $review->comment }}</p><div class="mt-2 flex items-center justify-between gap-2"><span class="text-[10px] text-gray-400">{{ $formatDate($review->created_at) }}</span><span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $review->is_approved ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $review->is_approved ? 'Disetujui' : 'Menunggu moderasi' }}</span></div>@if($review->media->isNotEmpty())<div class="mt-2 flex gap-2">@foreach($review->media as $media)<img src="{{ $media->path }}" alt="Media ulasan" class="h-12 w-12 rounded-md border border-gray-200 object-cover">@endforeach</div>@endif</div>
                    @empty
                        <p class="px-5 py-6 text-sm text-gray-500">Belum ada ulasan untuk produk ini.</p>
                    @endforelse
                </div>
            </section>

            <section class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-xs text-gray-500">
                <p>Dibuat: {{ $formatDate($product->created_at) }} · Diperbarui: {{ $formatDate($product->updated_at) }}</p>
                <p class="break-all">ID: {{ $product->id }}</p>
            </section>
        </div>
    </div>
</x-app-layout>
