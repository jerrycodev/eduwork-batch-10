<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Mengelola katalog produk dan menyinkronkan perubahan stok melalui InventoryService. */
class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $products = Product::when($request->search, function ($query, $search) {
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('sku', 'like', '%'.$search.'%')
                    ->orWhere('barcode', 'like', '%'.$search.'%');
            });
        })
            ->when($request->category_id, function ($query, $category_id) {
                $query->where('category_id', $category_id);
            })
            ->when($request->order, function ($query, $order) {
                switch ($order) {
                    case 'name':
                        $query->orderBy('name', 'asc');
                        break;
                    case 'name_desc':
                        $query->orderBy('name', 'desc');
                        break;
                    case 'price':
                        $query->orderBy('price', 'asc');
                        break;
                    case 'price_desc':
                        $query->orderBy('price', 'desc');
                        break;
                    case 'stock':
                        $query->orderBy('stock', 'asc');
                        break;
                    case 'stock_desc':
                        $query->orderBy('stock', 'desc');
                        break;
                }
            })
            ->with('images')
            ->paginate(5);
        $categories = ProductCategory::all();

        return view('admin.products.index', compact('products', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = ProductCategory::all();

        return view('admin.products.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    /** Validasi produk, gambar, opsi, dan varian sebelum menyimpan katalog dalam transaksi. */
    public function store(Request $request, InventoryService $inventory): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products,slug'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku'],
            'barcode' => ['nullable', 'string', 'max:100', 'unique:products,barcode'],
            'description' => ['required', 'string', 'max:10000'],
            'image' => ['required_without:images', 'nullable', 'string', 'max:255'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'primary_image' => ['nullable', 'string', 'max:80'],
            'price' => ['required', 'integer', 'min:0'],
            'compare_at_price' => ['nullable', 'integer', 'min:0', 'gte:price'],
            'cost_price' => ['nullable', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'weight_gram' => ['required', 'integer', 'min:1', 'max:1000000'],
            'length_cm' => ['nullable', 'numeric', 'min:0'],
            'width_cm' => ['nullable', 'numeric', 'min:0'],
            'height_cm' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'variant_images' => ['nullable', 'array'],
            'variant_images.*' => ['nullable', 'array', 'max:5'],
            'variant_images.*.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'options_json' => ['nullable', 'string', 'max:30000'],
            'variants' => ['nullable', 'array'],
            'variants.*.sku' => ['required', 'string', 'max:100'],
            'variants.*.barcode' => ['nullable', 'string', 'max:100'],
            'variants.*.price' => ['required', 'integer', 'min:0'],
            'variants.*.compare_at_price' => ['nullable', 'integer', 'min:0'],
            'variants.*.cost_price' => ['nullable', 'integer', 'min:0'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
            'variants.*.weight_gram' => ['required', 'integer', 'min:1', 'max:1000000'],
            'variants.*.length_cm' => ['nullable', 'numeric', 'min:0'],
            'variants.*.width_cm' => ['nullable', 'numeric', 'min:0'],
            'variants.*.height_cm' => ['nullable', 'numeric', 'min:0'],
            'variants.*.is_active' => ['nullable', 'boolean'],
            'category_id' => ['required', 'uuid', 'exists:product_categories,id'],
        ]);

        $uploadedImages = $request->file('images', []);
        unset($validated['variant_images']);
        if (blank($validated['image'] ?? null) && count($uploadedImages) === 0) {
            return back()->withErrors(['images' => 'Upload minimal satu gambar produk.'])->withInput();
        }

        DB::transaction(function () use ($validated, $request, $inventory, $uploadedImages): void {
            $initialStock = (int) $validated['stock'];
            $variantAttributes = [
                'price' => (int) $validated['price'],
                'compare_at_price' => $validated['compare_at_price'] ?? null,
                'cost_price' => $validated['cost_price'] ?? null,
                'weight_gram' => (int) $validated['weight_gram'],
                'length_cm' => $validated['length_cm'] ?? null,
                'width_cm' => $validated['width_cm'] ?? null,
                'height_cm' => $validated['height_cm'] ?? null,
            ];
            unset($validated['stock']);
            $validated['sku'] = filled($validated['sku'] ?? null) ? $validated['sku'] : 'PRD-'.strtoupper(Str::random(10));
            $validated['stock'] = 0;
            $validated['is_active'] = (bool) ($validated['is_active'] ?? true);
            $validated['is_featured'] = (bool) ($validated['is_featured'] ?? false);
            $product = Product::query()->create($validated);

            $variant = $product->defaultVariant()->firstOrFail();
            $variant->update($variantAttributes);
            $hasConfiguredVariants = $this->syncConfiguredVariants($product, $validated['options_json'] ?? null, $inventory, $request);

            $this->saveProductImages($product, $uploadedImages, $request->input('primary_image'));

            if (! $hasConfiguredVariants && $initialStock > 0) {
                $inventory->change(
                    productId: (string) $product->id,
                    variantId: (string) $variant->id,
                    delta: $initialStock,
                    type: 'in',
                    userId: (string) $request->user()->id,
                    notes: 'Initial stock when product was created',
                );
            }
        });

        return redirect()
            ->route('products.index')
            ->with('success', 'Product created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        // Muat relasi yang dipakai halaman detail sekaligus untuk menghindari N+1 query.
        $product->load([
            'category',
            'images',
            'allImages',
            'options.values',
            'variants.optionValues.option',
            'variants.images',
            'stockMovements' => fn ($query) => $query->with(['creator', 'variant'])->latest()->limit(10),
            'reviews' => fn ($query) => $query->with(['user', 'media'])->latest()->limit(5),
        ])->loadCount(['variants', 'options', 'images', 'reviews', 'approvedReviews', 'carts', 'orderItems', 'wishlistItems']);

        return view('admin.products.detail', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $product->load(['images', 'options.values', 'variants.optionValues.option', 'variants.images']);
        $categories = ProductCategory::all();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product, InventoryService $inventory)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products,slug,'.$product->id],
            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku,'.$product->id],
            'barcode' => ['nullable', 'string', 'max:100', 'unique:products,barcode,'.$product->id],
            'description' => ['required', 'string', 'max:10000'],
            'image' => ['required_without:images', 'nullable', 'string', 'max:255'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'primary_image' => ['nullable', 'string', 'max:80'],
            'price' => ['required', 'integer', 'min:0'],
            'compare_at_price' => ['nullable', 'integer', 'min:0'],
            'cost_price' => ['nullable', 'integer', 'min:0'],
            'stock' => ['sometimes', 'required', 'integer', 'min:0'],
            'weight_gram' => ['required', 'integer', 'min:1', 'max:1000000'],
            'length_cm' => ['nullable', 'numeric', 'min:0'],
            'width_cm' => ['nullable', 'numeric', 'min:0'],
            'height_cm' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'variant_images' => ['nullable', 'array'],
            'new_variant_images' => ['nullable', 'array'],
            'new_variant_images.*' => ['nullable', 'array', 'max:5'],
            'new_variant_images.*.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'additional_variants_json' => ['nullable', 'string', 'max:30000'],
            'variant_images.*' => ['nullable', 'array', 'max:5'],
            'variant_images.*.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'options_json' => ['nullable', 'string', 'max:30000'],
            'variants' => ['nullable', 'array'],
            'variants.*.sku' => ['required', 'string', 'max:100'],
            'variants.*.barcode' => ['nullable', 'string', 'max:100'],
            'variants.*.price' => ['required', 'integer', 'min:0'],
            'variants.*.compare_at_price' => ['nullable', 'integer', 'min:0'],
            'variants.*.cost_price' => ['nullable', 'integer', 'min:0'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
            'variants.*.weight_gram' => ['required', 'integer', 'min:1', 'max:1000000'],
            'variants.*.length_cm' => ['nullable', 'numeric', 'min:0'],
            'variants.*.width_cm' => ['nullable', 'numeric', 'min:0'],
            'variants.*.height_cm' => ['nullable', 'numeric', 'min:0'],
            'variants.*.is_active' => ['nullable', 'boolean'],
            'category_id' => ['required', 'uuid', 'exists:product_categories,id'],
        ]);

        $uploadedImages = $request->file('images', []);
        $additionalVariantsJson = $validated['additional_variants_json'] ?? null;

        DB::transaction(function () use ($validated, $product, $inventory, $request, $uploadedImages, $additionalVariantsJson): void {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);
            $variantUpdates = $validated['variants'] ?? [];
            $legacyVariantAttributes = [
                'sku' => $validated['sku'] ?? $lockedProduct->sku,
                'barcode' => $validated['barcode'] ?? null,
                'price' => (int) $validated['price'],
                'compare_at_price' => $validated['compare_at_price'] ?? null,
                'cost_price' => $validated['cost_price'] ?? null,
                'weight_gram' => (int) $validated['weight_gram'],
                'length_cm' => $validated['length_cm'] ?? null,
                'width_cm' => $validated['width_cm'] ?? null,
                'height_cm' => $validated['height_cm'] ?? null,
            ];
            $legacyTargetStock = isset($validated['stock']) ? (int) $validated['stock'] : null;
            unset(
                $validated['price'], $validated['compare_at_price'], $validated['cost_price'],
                $validated['stock'], $validated['weight_gram'], $validated['length_cm'],
                $validated['width_cm'], $validated['height_cm'], $validated['options_json'],
                $validated['variants'], $validated['variant_images'], $validated['new_variant_images'],
                $validated['additional_variants_json'],
            );
            $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
            $validated['is_featured'] = (bool) ($validated['is_featured'] ?? false);
            $lockedProduct->update($validated);
            if ($lockedProduct->variants()->exists() && $variantUpdates !== []) {
                foreach ($variantUpdates as $variantId => $variantData) {
                    $variant = $lockedProduct->variants()->lockForUpdate()->findOrFail($variantId);
                    $sku = trim((string) $variantData['sku']);
                    if (
                        ProductVariant::query()->where('sku', $sku)->whereKeyNot($variant->id)->exists() ||
                        Product::query()->where('sku', $sku)->whereKeyNot($lockedProduct->id)->exists()
                    ) {
                        throw ValidationException::withMessages(['variants' => 'SKU variant '.$sku.' sudah digunakan.']);
                    }
                    $barcode = $variantData['barcode'] ?? null;
                    if (filled($barcode) && (
                        ProductVariant::query()->where('barcode', $barcode)->whereKeyNot($variant->id)->exists() ||
                        Product::query()->where('barcode', $barcode)->whereKeyNot($lockedProduct->id)->exists()
                    )) {
                        throw ValidationException::withMessages(['variants' => 'Barcode variant '.$barcode.' sudah digunakan.']);
                    }

                    $variantTargetStock = (int) $variantData['stock'];
                    unset($variantData['stock']);
                    $variantData['is_active'] = (bool) ($variantData['is_active'] ?? false);
                    if (filled($variantData['compare_at_price'] ?? null) && (int) $variantData['compare_at_price'] < (int) $variantData['price']) {
                        throw ValidationException::withMessages(['variants' => 'Harga normal harus lebih besar atau sama dengan harga jual untuk SKU '.$sku.'.']);
                    }
                    $variant->update($variantData);
                    foreach ((array) $request->file('variant_images.'.$variantId, []) as $imageIndex => $file) {
                        $storedPath = $file->store("products/{$lockedProduct->id}/variants/{$variant->id}", 'public');
                        $variant->images()->create([
                            'product_id' => $lockedProduct->id,
                            'path' => Storage::disk('public')->url($storedPath),
                            'is_primary' => $variant->images()->doesntExist() && $imageIndex === 0,
                            'sort_order' => $variant->images()->max('sort_order') + 1,
                        ]);
                    }

                    if ((int) $variant->stock !== $variantTargetStock) {
                        $inventory->adjustTo(
                            productId: (string) $lockedProduct->id,
                            variantId: (string) $variant->id,
                            target: $variantTargetStock,
                            userId: (string) $request->user()->id,
                            notes: 'Variant stock adjusted from product edit form',
                        );
                    }
                }
            } elseif ($lockedProduct->variants()->exists()) {
                // Backward compatibility for older clients still submitting product-level pricing and stock fields.
                $legacyVariant = $lockedProduct->variants()->where('is_default', true)->first() ?? $lockedProduct->variants()->first();
                $legacyVariant->update($legacyVariantAttributes);
                if ($legacyTargetStock !== null && (int) $legacyVariant->stock !== $legacyTargetStock) {
                    $inventory->adjustTo(
                        productId: (string) $lockedProduct->id,
                        variantId: (string) $legacyVariant->id,
                        target: $legacyTargetStock,
                        userId: (string) $request->user()->id,
                        notes: 'Stock adjusted from legacy product edit payload',
                    );
                }
            }

            $this->addVariantsFromEditConfiguration($lockedProduct, $additionalVariantsJson, $inventory, $request);

            $this->ensureLegacyImageIsInGallery($lockedProduct);
            foreach ((array) $request->input('remove_images', []) as $imageId) {
                $image = $lockedProduct->images()->find($imageId);
                if ($image) {
                    $this->deleteStoredImage($image->path);
                    $image->delete();
                }
            }
            $this->saveProductImages($lockedProduct, $uploadedImages, $request->input('primary_image'));

            $variants = $lockedProduct->variants()->get();
            $defaultVariant = $variants->firstWhere('is_default', true) ?? $variants->first();
            $allVariantsSharePricing = $variants->every(fn (ProductVariant $variant) => $variant->compare_at_price !== null)
                && $variants->pluck('price')->unique()->count() === 1
                && $variants->pluck('compare_at_price')->unique()->count() === 1;

            $lockedProduct->update([
                'price' => (int) $variants->min('price'),
                'compare_at_price' => $allVariantsSharePricing ? (int) $variants->min('compare_at_price') : null,
                'cost_price' => $variants->whereNotNull('cost_price')->min('cost_price'),
                'stock' => (int) $variants->sum('stock'),
                'weight_gram' => $defaultVariant?->weight_gram ?? 1000,
                'length_cm' => $defaultVariant?->length_cm,
                'width_cm' => $defaultVariant?->width_cm,
                'height_cm' => $defaultVariant?->height_cm,
            ]);
        });

        return redirect()
            ->route('products.index')
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): RedirectResponse
    {
        // Soft-delete keeps historical order items, variants, stock movements, and images recoverable.
        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }

    /**
     * Add option values and new sellable variants from the product edit form.
     * Existing variants are intentionally never recreated or removed, preserving order history.
     */
    private function addVariantsFromEditConfiguration(Product $product, ?string $configuration, InventoryService $inventory, Request $request): void
    {
        if (blank($configuration)) {
            return;
        }

        try {
            $data = json_decode($configuration, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessages(['additional_variants_json' => 'Konfigurasi variant harus berupa JSON yang valid.']);
        }

        if (! is_array($data) || ! is_array($data['options'] ?? null) || ! is_array($data['variants'] ?? null) || $data['variants'] === [] || count($data['variants']) > 100) {
            throw ValidationException::withMessages(['additional_variants_json' => 'Konfigurasi harus memiliki option dan variant baru yang valid.']);
        }

        $valueIds = [];
        $optionCodes = [];
        foreach ($data['options'] as $optionIndex => $optionData) {
            if (! is_array($optionData) || blank($optionData['name'] ?? null) || blank($optionData['code'] ?? null) || ! in_array($optionData['display_type'] ?? 'button', ['color', 'button', 'dropdown'], true) || ! is_array($optionData['values'] ?? null) || $optionData['values'] === []) {
                throw ValidationException::withMessages(['additional_variants_json' => 'Setiap option wajib memiliki nama, tipe tampilan, dan minimal satu nilai.']);
            }

            $code = Str::slug((string) $optionData['code']);
            if ($code === '' || in_array($code, $optionCodes, true)) {
                throw ValidationException::withMessages(['additional_variants_json' => 'Kode option harus unik.']);
            }
            $optionCodes[] = $code;

            $isNewOption = blank($optionData['id'] ?? null);
            if (! $isNewOption) {
                $option = $product->options()->find($optionData['id']);
                if (! $option) {
                    throw ValidationException::withMessages(['additional_variants_json' => 'Option yang dipilih tidak ditemukan pada produk ini.']);
                }
                if ($product->options()->where('code', $code)->whereKeyNot($option->id)->exists()) {
                    throw ValidationException::withMessages(['additional_variants_json' => 'Kode option '.$code.' sudah digunakan.']);
                }
                $option->update([
                    'name' => trim((string) $optionData['name']),
                    'code' => $code,
                    'display_type' => $optionData['display_type'],
                    'sort_order' => $optionIndex,
                ]);
            } else {
                if ($product->options()->where('code', $code)->exists()) {
                    throw ValidationException::withMessages(['additional_variants_json' => 'Kode option '.$code.' sudah digunakan.']);
                }
                $option = $product->options()->create([
                    'name' => trim((string) $optionData['name']),
                    'code' => $code,
                    'display_type' => $optionData['display_type'],
                    'sort_order' => $optionIndex,
                ]);
            }

            foreach ($optionData['values'] as $valueIndex => $valueData) {
                if (! is_array($valueData) || blank($valueData['label'] ?? null) || blank($valueData['value'] ?? null)) {
                    throw ValidationException::withMessages(['additional_variants_json' => 'Setiap nilai option wajib memiliki label dan kode.']);
                }
                $valueCode = Str::slug((string) $valueData['value']);
                $hex = $valueData['color_hex'] ?? null;
                if (($optionData['display_type'] ?? 'button') === 'color' && blank($hex) && blank($valueData['swatch_image'] ?? null)) {
                    throw ValidationException::withMessages(['additional_variants_json' => 'Setiap swatch warna wajib memiliki kode HEX atau gambar.']);
                }
                if (filled($hex) && ! preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $hex)) {
                    throw ValidationException::withMessages(['additional_variants_json' => 'Kode warna harus menggunakan format HEX #RRGGBB.']);
                }

                if (filled($valueData['id'] ?? null)) {
                    $value = $option->values()->find($valueData['id']);
                    if (! $value) {
                        throw ValidationException::withMessages(['additional_variants_json' => 'Nilai option tidak cocok dengan option produk.']);
                    }
                    if ($option->values()->where('value', $valueCode)->whereKeyNot($value->id)->exists()) {
                        throw ValidationException::withMessages(['additional_variants_json' => 'Kode nilai '.$valueCode.' sudah digunakan.']);
                    }
                    $value->update([
                        'label' => trim((string) $valueData['label']),
                        'value' => $valueCode,
                        'color_hex' => $hex,
                        'swatch_image' => $valueData['swatch_image'] ?? null,
                        'sort_order' => $valueIndex,
                    ]);
                } else {
                    if ($option->values()->where('value', $valueCode)->exists()) {
                        throw ValidationException::withMessages(['additional_variants_json' => 'Nilai '.$valueCode.' sudah tersedia pada option '.$code.'.']);
                    }
                    $value = $option->values()->create([
                        'label' => trim((string) $valueData['label']),
                        'value' => $valueCode,
                        'color_hex' => $hex,
                        'swatch_image' => $valueData['swatch_image'] ?? null,
                        'sort_order' => $valueIndex,
                    ]);
                }
                $valueIds[$code][$valueCode] = $value->id;
            }

            // When introducing a brand-new option, map existing variants to its first value.
            // This keeps previously sellable variants valid after the storefront starts requiring the new option.
            if ($isNewOption) {
                $defaultValueId = reset($valueIds[$code]);
                if ($defaultValueId) {
                    $product->variants()->get()->each(
                        fn (ProductVariant $variant) => $variant->optionValues()->syncWithoutDetaching([$defaultValueId])
                    );
                }
            }
        }

        $existingCombinations = $product->variants()->with('optionValues')->get()
            ->map(function (ProductVariant $variant): string {
                $ids = $variant->optionValues->pluck('id')->sort()->values()->all();

                return implode('|', $ids);
            })->all();
        $seenCombinations = [];

        foreach ($data['variants'] as $variantIndex => $variantData) {
            $selection = $variantData['options'] ?? [];
            if (! is_array($selection) || count($selection) !== count($optionCodes) || array_diff($optionCodes, array_keys($selection)) !== []) {
                throw ValidationException::withMessages(['additional_variants_json' => 'Setiap variant harus memilih satu nilai dari setiap option.']);
            }

            $selectedIds = [];
            $labels = [];
            foreach ($optionCodes as $code) {
                $selectedValueId = $valueIds[$code][Str::slug((string) ($selection[$code] ?? ''))] ?? null;
                if (! $selectedValueId) {
                    throw ValidationException::withMessages(['additional_variants_json' => 'Variant memiliki pilihan option yang tidak dikenal.']);
                }
                $selectedIds[] = $selectedValueId;
                $labels[] = ProductOptionValue::query()->findOrFail($selectedValueId)->label;
            }
            $sortedIds = $selectedIds;
            sort($sortedIds);
            $combinationKey = implode('|', $sortedIds);
            if (in_array($combinationKey, $existingCombinations, true) || in_array($combinationKey, $seenCombinations, true)) {
                throw ValidationException::withMessages(['additional_variants_json' => 'Kombinasi variant tersebut sudah tersedia.']);
            }
            $seenCombinations[] = $combinationKey;

            $sku = trim((string) ($variantData['sku'] ?? ''));
            if ($sku === '' || strlen($sku) > 100 || ProductVariant::query()->where('sku', $sku)->exists() || Product::query()->where('sku', $sku)->exists()) {
                throw ValidationException::withMessages(['additional_variants_json' => 'SKU variant '.$sku.' tidak valid atau sudah digunakan.']);
            }
            $barcode = filled($variantData['barcode'] ?? null) ? trim((string) $variantData['barcode']) : null;
            if ($barcode && (strlen($barcode) > 100 || ProductVariant::query()->where('barcode', $barcode)->exists() || Product::query()->where('barcode', $barcode)->exists())) {
                throw ValidationException::withMessages(['additional_variants_json' => 'Barcode variant '.$barcode.' tidak valid atau sudah digunakan.']);
            }

            $price = filter_var($variantData['price'] ?? null, FILTER_VALIDATE_INT);
            $stock = filter_var($variantData['stock'] ?? null, FILTER_VALIDATE_INT);
            $weight = filter_var($variantData['weight_gram'] ?? null, FILTER_VALIDATE_INT);
            $compare = filled($variantData['compare_at_price'] ?? null) ? filter_var($variantData['compare_at_price'], FILTER_VALIDATE_INT) : null;
            $cost = filled($variantData['cost_price'] ?? null) ? filter_var($variantData['cost_price'], FILTER_VALIDATE_INT) : null;
            if ($price === false || $price < 0 || $stock === false || $stock < 0 || $weight === false || $weight < 1 || $weight > 1000000 || $compare === false || $cost === false || ($compare !== null && $compare < $price) || ($cost !== null && $cost < 0)) {
                throw ValidationException::withMessages(['additional_variants_json' => 'Harga, stok, harga normal, harga modal, atau berat variant tidak valid.']);
            }
            foreach (['length_cm', 'width_cm', 'height_cm'] as $dimension) {
                if (filled($variantData[$dimension] ?? null) && (! is_numeric($variantData[$dimension]) || (float) $variantData[$dimension] < 0 || (float) $variantData[$dimension] > 100000)) {
                    throw ValidationException::withMessages(['additional_variants_json' => 'Dimensi variant tidak valid.']);
                }
            }

            $variant = $product->variants()->create([
                'sku' => $sku,
                'barcode' => $barcode,
                'name' => implode(' / ', $labels),
                'price' => $price,
                'compare_at_price' => $compare,
                'cost_price' => $cost,
                'stock' => 0,
                'weight_gram' => $weight,
                'length_cm' => $variantData['length_cm'] ?? null,
                'width_cm' => $variantData['width_cm'] ?? null,
                'height_cm' => $variantData['height_cm'] ?? null,
                'is_default' => false,
                'is_active' => true,
            ]);
            $variant->optionValues()->sync($selectedIds);

            foreach ((array) $request->file('new_variant_images.'.$variantIndex, []) as $imageIndex => $file) {
                $storedPath = $file->store("products/{$product->id}/variants/{$variant->id}", 'public');
                $variant->images()->create([
                    'product_id' => $product->id,
                    'path' => Storage::disk('public')->url($storedPath),
                    'is_primary' => $imageIndex === 0,
                    'sort_order' => $imageIndex,
                ]);
            }

            if ($stock > 0) {
                $inventory->change(
                    productId: (string) $product->id,
                    variantId: (string) $variant->id,
                    delta: $stock,
                    type: 'in',
                    userId: (string) $request->user()->id,
                    notes: 'Initial stock for newly added variant '.$variant->sku,
                );
            }
        }
    }

    /**
     * Configure product attributes and sellable variant combinations from the admin form JSON.
     */
    private function syncConfiguredVariants(Product $product, ?string $configuration, InventoryService $inventory, Request $request): bool
    {
        if (blank($configuration)) {
            return false;
        }

        try {
            $data = json_decode($configuration, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessages(['options_json' => 'Konfigurasi variant harus berupa JSON yang valid.']);
        }

        if (! is_array($data) || ! is_array($data['options'] ?? null) || ! is_array($data['variants'] ?? null) || $data['variants'] === [] || (count($data['options']) === 0 && count($data['variants']) !== 1) || count($data['variants']) > 100) {
            throw ValidationException::withMessages(['options_json' => 'Konfigurasi harus berisi options dan variants yang tidak kosong.']);
        }

        $valueIds = [];
        $optionCodes = [];
        foreach ($data['options'] as $optionIndex => $optionData) {
            if (! is_array($optionData) || blank($optionData['name'] ?? null) || blank($optionData['code'] ?? null) || ! in_array($optionData['display_type'] ?? 'button', ['color', 'button', 'dropdown'], true) || ! is_array($optionData['values'] ?? null) || $optionData['values'] === []) {
                throw ValidationException::withMessages(['options_json' => 'Setiap option wajib memiliki name, code, display_type, dan minimal satu value.']);
            }

            $code = Str::slug((string) $optionData['code']);
            if ($code === '' || in_array($code, $optionCodes, true)) {
                throw ValidationException::withMessages(['options_json' => 'Kode option harus unik dan menggunakan huruf, angka, atau tanda hubung.']);
            }
            $optionCodes[] = $code;

            $option = $product->options()->create([
                'name' => trim((string) $optionData['name']),
                'code' => $code,
                'display_type' => $optionData['display_type'],
                'sort_order' => $optionIndex,
            ]);

            foreach ($optionData['values'] as $valueIndex => $valueData) {
                $hex = $valueData['color_hex'] ?? null;
                if ($hex !== null && ! preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $hex)) {
                    throw ValidationException::withMessages(['options_json' => 'Kode warna harus menggunakan format HEX #RRGGBB.']);
                }
                if (blank($valueData['label'] ?? null) || blank($valueData['value'] ?? null)) {
                    throw ValidationException::withMessages(['options_json' => 'Setiap nilai option wajib memiliki label dan value.']);
                }
                if (($optionData['display_type'] ?? 'button') === 'color' && blank($hex) && blank($valueData['swatch_image'] ?? null)) {
                    throw ValidationException::withMessages(['options_json' => 'Color swatch wajib memiliki color_hex atau swatch_image.']);
                }

                $value = $option->values()->create([
                    'label' => trim((string) $valueData['label']),
                    'value' => Str::slug((string) $valueData['value']),
                    'color_hex' => $hex,
                    'swatch_image' => $valueData['swatch_image'] ?? null,
                    'sort_order' => $valueIndex,
                ]);
                $valueIds[$code][$value->value] = $value->id;
            }
        }

        $product->defaultVariant()->delete();

        $seenCombinations = [];
        foreach ($data['variants'] as $variantIndex => $variantData) {
            $selection = $variantData['options'] ?? [];
            if (! is_array($selection) || count($selection) !== count($optionCodes) || array_diff($optionCodes, array_keys($selection)) !== []) {
                throw ValidationException::withMessages(['options_json' => 'Setiap variant harus memilih tepat satu value dari setiap option.']);
            }

            $selectedValueIds = [];
            $selectedLabels = $optionCodes === [] ? ['Default'] : [];
            foreach ($optionCodes as $code) {
                $selectedValueId = $valueIds[$code][Str::slug((string) ($selection[$code] ?? ''))] ?? null;
                if (! $selectedValueId) {
                    throw ValidationException::withMessages(['options_json' => 'Variant mengandung pilihan option yang tidak dikenal.']);
                }
                $selectedValueIds[] = $selectedValueId;
                $selectedLabels[] = ProductOptionValue::query()->findOrFail($selectedValueId)->label;
            }
            $combination = $selectedValueIds;
            sort($combination);
            $combinationKey = implode('|', $combination);
            if (in_array($combinationKey, $seenCombinations, true)) {
                throw ValidationException::withMessages(['options_json' => 'Kombinasi option setiap variant harus unik.']);
            }
            $seenCombinations[] = $combinationKey;

            $sku = filled($variantData['sku'] ?? null)
                ? trim((string) $variantData['sku'])
                : $product->sku.'-'.strtoupper(implode('-', array_map(fn ($value) => substr(Str::slug($value), 0, 12), $selectedLabels)));
            if (strlen($sku) > 100 || ProductVariant::query()->where('sku', $sku)->exists() || Product::query()->where('sku', $sku)->whereKeyNot($product->id)->exists()) {
                throw ValidationException::withMessages(['options_json' => 'SKU variant '.$sku.' tidak valid atau sudah digunakan.']);
            }
            $variantPrice = filter_var($variantData['price'] ?? $product->price, FILTER_VALIDATE_INT);
            $variantStock = filter_var($variantData['stock'] ?? 0, FILTER_VALIDATE_INT);
            $variantWeight = filter_var($variantData['weight_gram'] ?? $product->weight_gram ?? 1000, FILTER_VALIDATE_INT);
            $compareAtPrice = filled($variantData['compare_at_price'] ?? null) ? filter_var($variantData['compare_at_price'], FILTER_VALIDATE_INT) : null;
            $costPrice = filled($variantData['cost_price'] ?? null) ? filter_var($variantData['cost_price'], FILTER_VALIDATE_INT) : null;
            if ($variantPrice === false || $variantPrice < 0 || $variantStock === false || $variantStock < 0 || $variantWeight === false || $variantWeight < 1 || $variantWeight > 1000000 || $compareAtPrice === false || $costPrice === false || ($compareAtPrice !== null && $compareAtPrice < $variantPrice) || ($costPrice !== null && $costPrice < 0)) {
                throw ValidationException::withMessages(['options_json' => 'Harga, stok, harga normal, harga modal, atau berat variant tidak valid.']);
            }
            foreach (['length_cm', 'width_cm', 'height_cm'] as $dimension) {
                $dimensionValue = $variantData[$dimension] ?? null;
                if (filled($dimensionValue) && (! is_numeric($dimensionValue) || (float) $dimensionValue < 0 || (float) $dimensionValue > 100000)) {
                    throw ValidationException::withMessages(['options_json' => 'Dimensi variant harus berupa angka positif yang valid.']);
                }
            }
            $barcode = $variantData['barcode'] ?? null;
            if (filled($barcode) && (strlen((string) $barcode) > 100 || ProductVariant::query()->where('barcode', $barcode)->exists() || Product::query()->where('barcode', $barcode)->whereKeyNot($product->id)->exists())) {
                throw ValidationException::withMessages(['options_json' => 'Barcode variant tidak valid atau sudah digunakan.']);
            }

            $variant = $product->variants()->create([
                'sku' => $sku,
                'barcode' => $variantData['barcode'] ?? null,
                'name' => $variantData['name'] ?? implode(' / ', $selectedLabels),
                'price' => $variantPrice,
                'compare_at_price' => $compareAtPrice,
                'cost_price' => $costPrice,
                'stock' => 0,
                'weight_gram' => $variantWeight,
                'length_cm' => $variantData['length_cm'] ?? $product->length_cm,
                'width_cm' => $variantData['width_cm'] ?? $product->width_cm,
                'height_cm' => $variantData['height_cm'] ?? $product->height_cm,
                'is_default' => $variantIndex === 0,
                'is_active' => (bool) ($variantData['is_active'] ?? true),
            ]);
            $variant->optionValues()->sync($selectedValueIds);

            foreach ((array) $request->file('variant_images.'.$variantIndex, []) as $imageIndex => $file) {
                $storedPath = $file->store("products/{$product->id}/variants/{$variant->id}", 'public');
                $variant->images()->create([
                    'product_id' => $product->id,
                    'path' => Storage::disk('public')->url($storedPath),
                    'is_primary' => $imageIndex === 0,
                    'sort_order' => $imageIndex,
                ]);
            }

            $stock = $variantStock;
            if ($stock > 0) {
                $inventory->change(
                    productId: (string) $product->id,
                    variantId: (string) $variant->id,
                    delta: $stock,
                    type: 'in',
                    userId: (string) $request->user()->id,
                    notes: 'Initial stock for variant '.$variant->sku,
                );
            }
        }

        $createdVariants = $product->variants()->get();
        $defaultVariant = $createdVariants->firstWhere('is_default', true) ?? $createdVariants->first();
        $allVariantsSharePricing = $createdVariants->every(fn (ProductVariant $variant) => $variant->compare_at_price !== null)
            && $createdVariants->pluck('price')->unique()->count() === 1
            && $createdVariants->pluck('compare_at_price')->unique()->count() === 1;
        $product->update([
            'price' => (int) $createdVariants->min('price'),
            'compare_at_price' => $allVariantsSharePricing ? (int) $createdVariants->min('compare_at_price') : null,
            'cost_price' => $createdVariants->whereNotNull('cost_price')->min('cost_price'),
            'stock' => (int) $createdVariants->sum('stock'),
            'weight_gram' => $defaultVariant?->weight_gram ?? 1000,
            'length_cm' => $defaultVariant?->length_cm,
            'width_cm' => $defaultVariant?->width_cm,
            'height_cm' => $defaultVariant?->height_cm,
        ]);

        return true;
    }

    private function ensureLegacyImageIsInGallery(Product $product): void
    {
        if (blank($product->image)) {
            return;
        }

        $alreadyStored = $product->images()->where('path', $product->image)->exists();
        if ($alreadyStored) {
            return;
        }

        $hasPrimary = $product->images()->where('is_primary', true)->exists();
        $product->images()->create([
            'path' => $product->image,
            'is_primary' => ! $hasPrimary,
            'sort_order' => $product->images()->max('sort_order') + 1,
        ]);
    }

    /** @param array<int, UploadedFile> $files */
    private function saveProductImages(Product $product, array $files, ?string $primarySelection): void
    {
        if ($primarySelection === 'legacy' && $product->images()->doesntExist() && $files === []) {
            return;
        }

        $this->ensureLegacyImageIsInGallery($product);
        $existingImages = $product->images()->get();
        $newImages = [];

        foreach ($files as $index => $file) {
            $path = $file->store("products/{$product->id}", 'public');
            $newImages[$index] = $product->images()->create([
                'path' => Storage::disk('public')->url($path),
                'is_primary' => false,
                'sort_order' => $existingImages->count() + $index,
            ]);
        }

        if ($primarySelection !== null && str_starts_with($primarySelection, 'existing:')) {
            $primaryId = substr($primarySelection, 9);
        } elseif ($primarySelection !== null && str_starts_with($primarySelection, 'new:')) {
            $primaryId = $newImages[(int) substr($primarySelection, 4)]->id ?? null;
        } else {
            $primaryId = $existingImages->firstWhere('is_primary', true)?->id
                ?? $existingImages->first()?->id
                ?? collect($newImages)->first()?->id;
        }

        $primaryImage = $primaryId ? $product->images()->find($primaryId) : null;

        if (! $primaryImage) {
            $primaryImage = $product->images()->orderBy('sort_order')->first();
        }

        $product->images()->update(['is_primary' => false]);

        if ($primaryImage) {
            $primaryImage->update(['is_primary' => true]);
            $product->forceFill(['image' => $primaryImage->path])->save();
        } else {
            $product->forceFill(['image' => null])->save();
        }
    }

    private function deleteStoredImage(?string $url): void
    {
        if (! $url) {
            return;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if ($path && Str::startsWith($path, '/storage/')) {
            Storage::disk('public')->delete(Str::after($path, '/storage/'));
        }
    }
}
