{{-- Dokumentasi: Komponen form produk yang digunakan pada halaman create dan edit. --}}
{{-- Alpine menandai submit berjalan agar tombol tidak terkirim berulang kali. --}}
<form id="product-form" action="{{ $action }}" method="POST" enctype="multipart/form-data" class="space-y-8" autocomplete="off" x-data="adminProductForm" @submit="startSubmit()">
    @csrf
    @if ($product)
        @method('PUT')
    @endif
    <input type="hidden" name="image" value="{{ old('image', $product?->image) }}">
    {{-- Product-level values are denormalized summaries; sellable pricing and inventory live on variants. --}}
    <input type="hidden" id="price" name="price" value="{{ old('price', $product?->price ?? 0) }}">
    <input type="hidden" id="compare_at_price" name="compare_at_price" value="{{ old('compare_at_price', $product?->compare_at_price) }}">
    <input type="hidden" id="cost_price" name="cost_price" value="{{ old('cost_price', $product?->cost_price) }}">
    <input type="hidden" id="stock" name="stock" value="{{ old('stock', $product?->stock ?? 0) }}">
    <input type="hidden" id="weight_gram" name="weight_gram" value="{{ old('weight_gram', $product?->weight_gram ?? 1000) }}">
    <input type="hidden" id="length_cm" name="length_cm" value="{{ old('length_cm', $product?->length_cm) }}">
    <input type="hidden" id="width_cm" name="width_cm" value="{{ old('width_cm', $product?->width_cm) }}">
    <input type="hidden" id="height_cm" name="height_cm" value="{{ old('height_cm', $product?->height_cm) }}">

    <div class="grid grid-cols-1 gap-6">
        <div class="space-y-2">
            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
            <input id="name" name="name" type="text" value="{{ old('name', $product?->name) }}" required
                class="block w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-primary-500 focus:ring-4 focus:ring-primary-100">
            @error('name') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="space-y-2">
            <label for="slug" class="block text-sm font-medium text-gray-700">Slug</label>
            <input id="slug" name="slug" type="text" value="{{ old('slug', $product?->slug) }}" required
                class="block w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-primary-500 focus:ring-4 focus:ring-primary-100">
            @error('slug') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="space-y-2">
            <label for="sku" class="block text-sm font-medium text-gray-700">SKU</label>
            <input id="sku" name="sku" type="text" value="{{ old('sku', $product?->sku) }}" {{ $product ? 'required' : '' }}
                placeholder="Otomatis jika dikosongkan"
                class="block w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm">
            @error('sku') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="space-y-2">
            <label for="barcode" class="block text-sm font-medium text-gray-700">Barcode (opsional)</label>
            <input id="barcode" name="barcode" type="text" value="{{ old('barcode', $product?->barcode) }}"
                class="block w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm">
            @error('barcode') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="space-y-2 md:col-span-2">
            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
            <textarea id="description" name="description" rows="4" required
                class="block w-full resize-y rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-primary-500 focus:ring-4 focus:ring-primary-100">{{ old('description', $product?->description) }}</textarea>
            @error('description') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="space-y-2">
            <label for="category_id" class="block text-sm font-medium text-gray-700">Category</label>
            <select id="category_id" name="category_id" required
                class="block w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-primary-500 focus:ring-4 focus:ring-primary-100">
                <option value="" disabled @selected(! old('category_id', $product?->category_id))>Choose a category</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('category_id', $product?->category_id) === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            @error('category_id') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-5 md:col-span-2">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product?->is_active ?? true))>
                Produk aktif
            </label>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product?->is_featured ?? false))>
                Produk unggulan
            </label>
        </div>
        <div class="space-y-2 md:col-span-2">
            <label for="meta_title" class="block text-sm font-medium text-gray-700">SEO title</label>
            <input id="meta_title" name="meta_title" type="text" maxlength="255" value="{{ old('meta_title', $product?->meta_title) }}"
                class="block w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm">
        </div>
        <div class="space-y-2 md:col-span-2">
            <label for="meta_description" class="block text-sm font-medium text-gray-700">SEO description</label>
            <textarea id="meta_description" name="meta_description" rows="2"
                class="block w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm">{{ old('meta_description', $product?->meta_description) }}</textarea>
        </div>
        @if ($product && $product->variants->isNotEmpty())
            <div class="space-y-3 md:col-span-2">
                <div>
                    <h4 class="text-sm font-semibold text-gray-800">Variants & Inventory</h4>
                    <p class="mt-1 text-xs text-gray-500">Manage each variant's SKU, price, discount, cost, stock, weight, dimensions, images, and active status.</p>
                </div>
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-[1500px] divide-y divide-gray-200 text-left text-xs">
                        <thead class="bg-gray-50 text-gray-500">
                            <tr><th class="px-3 py-2">SKU</th><th class="px-3 py-2">Options</th><th class="px-3 py-2">Price (Rp)</th><th class="px-3 py-2">Compare at</th><th class="px-3 py-2">Cost</th><th class="px-3 py-2">Stock</th><th class="px-3 py-2">Weight (g)</th><th class="px-3 py-2">Length</th><th class="px-3 py-2">Width</th><th class="px-3 py-2">Height</th><th class="px-3 py-2">Variant images</th><th class="px-3 py-2">Active</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($product->variants as $variant)
                                <tr data-existing-variant-row>
                                    <td class="min-w-40 px-3 py-2">
                                        <input name="variants[{{ $variant->id }}][sku]" value="{{ old('variants.'.$variant->id.'.sku', $variant->sku) }}" required class="w-36 rounded-md border-gray-300 text-xs">
                                        <input name="variants[{{ $variant->id }}][barcode]" value="{{ old('variants.'.$variant->id.'.barcode', $variant->barcode) }}" placeholder="Barcode" class="mt-1 w-36 rounded-md border-gray-300 text-xs">
                                    </td>
                                    <td class="px-3 py-2 text-gray-600">{{ $variant->optionValues->map(fn ($value) => $value->option->name.': '.$value->label)->implode(' / ') ?: 'Default' }}</td>
                                    <td class="px-3 py-2"><input name="variants[{{ $variant->id }}][price]" data-v-price type="number" min="0" value="{{ old('variants.'.$variant->id.'.price', $variant->price) }}" required class="w-28 rounded-md border-gray-300 text-xs"></td>
                                    <td class="px-3 py-2"><input name="variants[{{ $variant->id }}][compare_at_price]" data-v-compare type="number" min="0" value="{{ old('variants.'.$variant->id.'.compare_at_price', $variant->compare_at_price) }}" placeholder="—" class="w-24 rounded-md border-gray-300 text-xs"></td>
                                    <td class="px-3 py-2"><input name="variants[{{ $variant->id }}][cost_price]" data-v-cost type="number" min="0" value="{{ old('variants.'.$variant->id.'.cost_price', $variant->cost_price) }}" placeholder="—" class="w-24 rounded-md border-gray-300 text-xs"></td>
                                    <td class="px-3 py-2"><input name="variants[{{ $variant->id }}][stock]" data-v-stock type="number" min="0" value="{{ old('variants.'.$variant->id.'.stock', $variant->stock) }}" required class="w-20 rounded-md border-gray-300 text-xs"></td>
                                    <td class="px-3 py-2"><input name="variants[{{ $variant->id }}][weight_gram]" data-v-weight type="number" min="1" value="{{ old('variants.'.$variant->id.'.weight_gram', $variant->weight_gram) }}" required class="w-20 rounded-md border-gray-300 text-xs"></td>
                                    <td class="px-3 py-2"><input name="variants[{{ $variant->id }}][length_cm]" data-v-length type="number" min="0" step="0.01" value="{{ old('variants.'.$variant->id.'.length_cm', $variant->length_cm) }}" class="w-20 rounded-md border-gray-300 text-xs"></td>
                                    <td class="px-3 py-2"><input name="variants[{{ $variant->id }}][width_cm]" data-v-width type="number" min="0" step="0.01" value="{{ old('variants.'.$variant->id.'.width_cm', $variant->width_cm) }}" class="w-20 rounded-md border-gray-300 text-xs"></td>
                                    <td class="px-3 py-2"><input name="variants[{{ $variant->id }}][height_cm]" data-v-height type="number" min="0" step="0.01" value="{{ old('variants.'.$variant->id.'.height_cm', $variant->height_cm) }}" class="w-20 rounded-md border-gray-300 text-xs"></td>
                                    <td class="min-w-48 px-3 py-2">
                                        <div class="mb-2 flex flex-wrap gap-1">
                                            @foreach ($variant->images as $variantImage)
                                                <img src="{{ $variantImage->path }}" alt="{{ $variant->name }}" class="h-10 w-10 rounded-md border border-gray-200 object-cover">
                                            @endforeach
                                        </div>
                                        <input type="file" name="variant_images[{{ $variant->id }}][]" data-variant-file accept="image/jpeg,image/png,image/webp" multiple class="block w-48 text-[10px] file:mr-2 file:rounded file:border-0 file:bg-primary-50 file:px-2 file:py-1 file:text-[10px] file:font-semibold file:text-primary-700">
                                        <div data-variant-preview class="mt-2 flex flex-wrap gap-1"></div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="hidden" name="variants[{{ $variant->id }}][is_active]" value="0">
                                        <input type="checkbox" name="variants[{{ $variant->id }}][is_active]" value="1" @checked(old('variants.'.$variant->id.'.is_active', $variant->is_active)) class="rounded border-gray-300 text-primary-600">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
        @if ($product)
            {{-- Builder edit hanya menambahkan kombinasi baru; varian lama tetap dipertahankan. --}}
            <div class="space-y-4 md:col-span-2" data-edit-variant-builder>
                <input type="hidden" name="additional_variants_json" id="additional-variants-json">
                <input type="hidden" data-edit-variant-seed value="{{ json_encode([
                    'options' => $product->options->map(fn ($option) => [
                        'id' => $option->id,
                        'name' => $option->name,
                        'code' => $option->code,
                        'display_type' => $option->display_type,
                        'values' => $option->values->map(fn ($value) => [
                            'id' => $value->id,
                            'label' => $value->label,
                            'value' => $value->value,
                            'color_hex' => $value->color_hex,
                            'swatch_image' => $value->swatch_image,
                        ])->values(),
                    ])->values(),
                    'combinations' => $product->variants->map(fn ($variant) => $variant->optionValues->pluck('id')->sort()->values())->values(),
                ]) }}">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-800">Tambah Variant Baru</h4>
                        <p class="mt-1 text-xs text-gray-500">Tambahkan option atau nilai option, lalu generate kombinasi yang belum tersedia. Variant lama tetap dipertahankan.</p>
                    </div>
                    <button type="button" data-edit-add-option class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">+ Add option</button>
                </div>
                <div data-edit-option-groups class="space-y-3"></div>
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" data-edit-generate class="rounded-lg bg-primary-50 px-4 py-2 text-xs font-semibold text-primary-700 hover:bg-primary-100">Generate new variants</button>
                    <span class="text-xs text-gray-500">Kombinasi yang sudah ada akan dilewati secara otomatis.</span>
                </div>
                <div data-edit-new-variant-rows class="overflow-x-auto"></div>
                @error('additional_variants_json') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                @vite('resources/js/product-variants.edit.js')
            </div>
        @endif
        @if (! $product)
            {{-- Builder create menghasilkan kombinasi varian dari option yang dipilih admin. --}}
            <div class="space-y-4 md:col-span-2" data-variant-builder>
                <input type="hidden" name="options_json" id="options-json" value="{{ old('options_json') }}">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-800">Variants & Inventory</h4>
                        <p class="mt-1 text-xs text-gray-500">Manage options and combinations. Every product has at least one sellable variant; if you do not add options, a Default variant will be created.</p>
                    </div>
                    <button type="button" data-add-option class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">+ Add option</button>
                </div>
                <div data-option-groups class="space-y-3"></div>
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" data-generate-variants class="rounded-lg bg-primary-50 px-4 py-2 text-xs font-semibold text-primary-700 hover:bg-primary-100">Generate variants</button>
                    <span class="text-xs text-gray-500">Set SKU, barcode, selling price, normal price, cost, stock, weight, dimensions, images, and status for every variant.</span>
                </div>
                <div data-variant-rows class="overflow-x-auto"></div>
                @error('options_json') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                @error('variant_images.*.*') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                @vite('resources/js/product-variants.builder.js')
            </div>
        @endif
        {{-- Galeri menggunakan Alpine untuk preview file sementara dan pilihan gambar utama. --}}
        <div class="space-y-3 md:col-span-2" data-product-gallery x-data="adminProductGallery">
            <div>
                <label for="product-images" class="block text-sm font-medium text-gray-700">Product images</label>
                <p class="mt-1 text-xs text-gray-500">Upload hingga 10 gambar produk (JPG, PNG, WEBP; maksimal 5 MB per gambar). Gambar spesifik ukuran/warna diunggah pada tab Variants & Inventory.</p>
            </div>
            @if ($product && $product->images->isNotEmpty())
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ($product->images as $image)
                        <label class="relative rounded-xl border border-gray-200 p-2">
                            <img src="{{ $image->path }}" alt="Product image" class="aspect-square w-full rounded-lg object-cover">
                            <span class="mt-2 flex items-center gap-2 text-xs"><input type="radio" name="primary_image" value="existing:{{ $image->id }}" @checked($image->is_primary)> Main image</span>
                            <span class="mt-1 flex items-center gap-2 text-xs text-red-600"><input type="checkbox" name="remove_images[]" value="{{ $image->id }}"> Remove image</span>
                        </label>
                    @endforeach
                </div>
            @elseif ($product?->image)
                <div class="flex items-center gap-3 rounded-lg border border-gray-200 p-3"><img src="{{ $product->image }}" alt="Current product image" class="h-16 w-16 rounded-lg object-cover"><span class="text-sm text-gray-600">Current image. Upload new images to add them to the gallery.</span><label class="ml-auto flex items-center gap-2 text-xs"><input type="radio" name="primary_image" value="legacy" checked> Main image</label><span data-file-delete-existing class="sr-only">Current image</span></div>
            @endif
            <input id="product-images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple @change="handleFiles($event)" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-primary-700">
            <p data-file-upload-error class="hidden text-sm text-red-600" role="alert"></p>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <template x-for="preview in previews" :key="preview.url">
                    <label class="rounded-xl border border-gray-200 p-2">
                        <img :src="preview.url" :alt="preview.name" class="aspect-square w-full rounded-lg object-cover">
                        <span class="mt-2 flex items-center gap-2 text-xs"><input data-new-image-radio type="radio" name="primary_image" :value="preview.value"> Gambar utama</span>
                    </label>
                </template>
            </div>
            @error('images') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            @error('images.*') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            @error('image') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-6 sm:flex-row sm:justify-end">
        <button type="reset" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-100">Reset</button>
        <button type="submit" :disabled="submitting" class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 disabled:cursor-wait disabled:opacity-60 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-200">
            <i data-lucide="save" class="h-4 w-4"></i><span x-text="submitting ? 'Menyimpan...' : '{{ $submitLabel ?? 'Save changes' }}'">{{ $submitLabel ?? 'Save changes' }}</span>
        </button>
    </div>
</form>

@push('scripts')
    @vite('resources/js/product.form.js')
@endpush