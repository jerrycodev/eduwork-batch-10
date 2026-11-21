<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use Database\Seeders\ProductCategorySeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ProductSeeder extends Seeder
{
    /**
     * Seed a realistic fashion catalog for local development and testing.
     *
     * Every product has Color and Size options and multiple sellable variants.
     * Re-running this seeder updates the demo records without duplicating them.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing', 'development'])) {
            return;
        }

        $this->call(ProductCategorySeeder::class);

        foreach ($this->products() as $index => $definition) {
            DB::transaction(function () use ($definition, $index): void {
                $category = ProductCategory::query()
                    ->where('slug', $definition['category'])
                    ->first();

                if (! $category) {
                    throw new RuntimeException("Product category [{$definition['category']}] was not created.");
                }

                $productSku = sprintf('FASH-%03d', $index + 1);
                $slug = Str::slug($definition['name']);

                // Suppress Product's legacy auto-default-variant hook. This seeder
                // creates the complete variant set explicitly below.
                $product = Product::withoutEvents(fn () => Product::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $definition['name'],
                        'description' => $definition['description'],
                        'image' => $definition['image'],
                        'price' => $definition['base_price'],
                        'stock' => 0,
                        'weight_gram' => $definition['weight_gram'],
                        'category_id' => $category->id,
                        'sku' => $productSku,
                        'is_active' => true,
                        'is_featured' => $definition['featured'],
                        'meta_title' => $definition['name'] . ' | RevStore',
                        'meta_description' => Str::limit($definition['description'], 155),
                        'length_cm' => $definition['dimensions'][0],
                        'width_cm' => $definition['dimensions'][1],
                        'height_cm' => $definition['dimensions'][2],
                    ],
                ));

                $colorOption = ProductOption::query()->updateOrCreate(
                    ['product_id' => $product->id, 'code' => 'color'],
                    ['name' => 'Warna', 'display_type' => 'color', 'sort_order' => 1],
                );

                $sizeOption = ProductOption::query()->updateOrCreate(
                    ['product_id' => $product->id, 'code' => 'size'],
                    ['name' => $definition['size_label'], 'display_type' => 'button', 'sort_order' => 2],
                );

                $colorValues = [];
                foreach ($definition['colors'] as $position => [$value, $label, $hex]) {
                    $colorValues[$value] = ProductOptionValue::query()->updateOrCreate(
                        ['product_option_id' => $colorOption->id, 'value' => $value],
                        [
                            'label' => $label,
                            'color_hex' => $hex,
                            'sort_order' => $position,
                        ],
                    );
                }

                $sizeValues = [];
                foreach ($definition['sizes'] as $position => $size) {
                    $sizeValues[$size] = ProductOptionValue::query()->updateOrCreate(
                        ['product_option_id' => $sizeOption->id, 'value' => $size],
                        ['label' => $size, 'color_hex' => null, 'sort_order' => $position],
                    );
                }

                $variantSkus = [];
                $variantIndex = 0;
                $colorIndex = 0;

                foreach ($colorValues as $color => $colorValue) {
                    $variantImage = $definition['variants_images'][$color]
                        ?? $definition['variants_images'][$colorIndex]
                        ?? $definition['image'];

                    foreach ($sizeValues as $size => $sizeValue) {
                        $variantSku = sprintf('%s-%s-%s', $productSku, strtoupper($color), strtoupper($size));
                        $variantSkus[] = $variantSku;
                        $variantIndex++;

                        $price = $definition['base_price'] + ($variantIndex % 3 === 0 ? 25000 : 0);
                        $stock = 8 + (($index + $variantIndex) % 18);

                        $variant = ProductVariant::query()->firstOrNew(['sku' => $variantSku]);
                        $isNewVariant = ! $variant->exists;
                        $variant->fill([
                            'product_id' => $product->id,
                            'barcode' => null,
                            'name' => $colorValue->label . ' / ' . $sizeValue->label,
                            'price' => $price,
                            'compare_at_price' => $price + 100000,
                            'cost_price' => (int) round($price * 0.55),
                            // Stok yang sudah dikelola admin tidak boleh di-reset saat seeder dijalankan ulang.
                            'stock' => $isNewVariant ? $stock : $variant->stock,
                            'weight_gram' => $definition['weight_gram'],
                            'length_cm' => $definition['dimensions'][0],
                            'width_cm' => $definition['dimensions'][1],
                            'height_cm' => $definition['dimensions'][2],
                            'is_default' => $variantIndex === 1,
                            'is_active' => true,
                        ])->save();

                        $variant->optionValues()->sync([$colorValue->id, $sizeValue->id]);
                        $variant->images()->updateOrCreate(
                            ['sort_order' => 0],
                            [
                                'product_id' => $product->id,
                                'path' => $variantImage,
                                'is_primary' => false,
                            ],
                        );
                    }

                    $colorIndex++;
                }

                // Remove obsolete variants belonging to this seeded product only.
                $product->variants()->whereNotIn('sku', $variantSkus)->delete();

                $variants = $product->variants()->get();
                $product->forceFill([
                    'price' => $variants->min('price'),
                    'stock' => $variants->sum('stock'),
                    'compare_at_price' => $variants->min('compare_at_price'),
                    'cost_price' => $variants->min('cost_price'),
                    'weight_gram' => $variants->firstWhere('is_default', true)?->weight_gram ?? $definition['weight_gram'],
                ])->save();

                // Keep the product-level image as the primary gallery image.
                $product->images()->update(['is_primary' => false]);
                $product->images()->updateOrCreate(
                    ['sort_order' => 0],
                    ['path' => $definition['image'], 'is_primary' => true],
                );
            });
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function products(): array
    {
        return [
            [
                'name' => 'Essential Cotton T-Shirt',
                'category' => 'atasan',
                'description' => 'Kaos basic berbahan katun lembut dengan potongan regular fit, nyaman untuk aktivitas sehari-hari.',
                'base_price' => 129000,
                'weight_gram' => 220,
                'dimensions' => [null, null, null],
                'size_label' => 'Ukuran',
                'sizes' => ['S', 'M', 'L'],
                'colors' => [['black', 'Hitam', '#171717'], ['white', 'Putih', '#F5F5F4'], ['navy', 'Navy', '#1E3A5F']],
                'featured' => true,
                'image' => '/storage/seeders/products/essential-cotton-t-shirt.jpg',
                'variants_images' => [
                    'black' => '/storage/seeders/products/essential-cotton-t-shirt-black.jpg',
                    'white' => '/storage/seeders/products/essential-cotton-t-shirt-white.jpg',
                    'navy' => '/storage/seeders/products/essential-cotton-t-shirt-navy.jpg',
                ],
            ],
            [
                'name' => 'Classic Pique Polo',
                'category' => 'atasan',
                'description' => 'Polo shirt pique dengan kerah klasik dan detail kancing minimalis untuk tampilan smart casual.',
                'base_price' => 189000,
                'weight_gram' => 280,
                'dimensions' => [null, null, null],
                'size_label' => 'Ukuran',
                'sizes' => ['M', 'L', 'XL'],
                'colors' => [['black', 'Hitam', '#171717'], ['cream', 'Krem', '#E8DDC8'], ['forest', 'Forest Green', '#315747']],
                'featured' => false,
                'image' => '/storage/seeders/products/classic-pique-polo.jpg',
                'variants_images' => [
                    'black' => '/storage/seeders/products/classic-pique-polo-black.jpg',
                    'cream' => '/storage/seeders/products/classic-pique-polo-cream.jpg',
                    'forest' => '/storage/seeders/products/classic-pique-polo-forest.jpg',
                ],
            ],
            [
                'name' => 'Oxford Casual Shirt',
                'category' => 'atasan',
                'description' => 'Kemeja oxford serbaguna dengan tekstur kain halus, cocok dipadukan dengan celana chino maupun denim.',
                'base_price' => 259000,
                'weight_gram' => 320,
                'dimensions' => [null, null, null],
                'size_label' => 'Ukuran',
                'sizes' => ['M', 'L', 'XL'],
                'colors' => [['sky', 'Sky Blue', '#9FC7DD'], ['white', 'Putih', '#F5F5F4'], ['blue', 'Biru', '#345B8C']],
                'featured' => true,
                'image' => '/storage/seeders/products/oxford-casual-shirt.jpg',
                'variants_images' => [
                    'sky' => '/storage/seeders/products/oxford-casual-shirt-sky.jpg',
                    'white' => '/storage/seeders/products/oxford-casual-shirt-white.jpg',
                    'blue' => '/storage/seeders/products/oxford-casual-shirt-blue.jpg',
                ],
            ],
            [
                'name' => 'Relaxed Fit Denim Jeans',
                'category' => 'bawahan',
                'description' => 'Celana denim relaxed fit dengan bahan kokoh namun tetap nyaman untuk digunakan sepanjang hari.',
                'base_price' => 329000,
                'weight_gram' => 650,
                'dimensions' => [null, null, null],
                'size_label' => 'Ukuran',
                'sizes' => ['30', '32', '34'],
                'colors' => [['indigo', 'Indigo', '#263B63'], ['black', 'Black Denim', '#252525']],
                'featured' => true,
                'image' => '/storage/seeders/products/relaxed-fit-denim-jeans.jpg',
                'variants_images' => [
                    'indigo' => '/storage/seeders/products/relaxed-fit-denim-jeans-indigo.jpg',
                    'black' => '/storage/seeders/products/relaxed-fit-denim-jeans-black.jpg',
                ],
            ],
            [
                'name' => 'Everyday Chino Pants',
                'category' => 'bawahan',
                'description' => 'Celana chino dengan siluet tapered dan material stretch ringan untuk gaya kasual rapi.',
                'base_price' => 279000,
                'weight_gram' => 520,
                'dimensions' => [null, null, null],
                'size_label' => 'Ukuran',
                'sizes' => ['30', '32', '34'],
                'colors' => [['khaki', 'Khaki', '#B7A27A'], ['black', 'Hitam', '#171717'], ['olive', 'Olive', '#626B45']],
                'featured' => false,
                'image' => '/storage/seeders/products/everyday-chino-pants.jpg',
                'variants_images' => [
                    'khaki' => '/storage/seeders/products/everyday-chino-pants-khaki.jpg',
                    'black' => '/storage/seeders/products/everyday-chino-pants-black.jpg',
                    'olive' => '/storage/seeders/products/everyday-chino-pants-olive.jpg',
                ],
            ],
            [
                'name' => 'Minimal Zip Hoodie',
                'category' => 'outerwear',
                'description' => 'Hoodie zipper minimalis dengan fleece lembut, saku depan, dan potongan relaxed untuk layering.',
                'base_price' => 349000,
                'weight_gram' => 720,
                'dimensions' => [null, null, null],
                'size_label' => 'Ukuran',
                'sizes' => ['M', 'L', 'XL'],
                'colors' => [['charcoal', 'Charcoal', '#424242'], ['oat', 'Oat', '#D8CDBB'], ['navy', 'Navy', '#1E3A5F']],
                'featured' => true,
                'image' => '/storage/seeders/products/minimal-zip-hoodie.jpg',
                'variants_images' => [
                    'charcoal' => '/storage/seeders/products/minimal-zip-hoodie-charcoal.jpg',
                    'oat' => '/storage/seeders/products/minimal-zip-hoodie-oat.jpg',
                    'navy' => '/storage/seeders/products/minimal-zip-hoodie-navy.jpg',
                ],
            ],
            [
                'name' => 'Linen Blend Midi Dress',
                'category' => 'dress',
                'description' => 'Midi dress berbahan linen blend dengan detail pinggang dan tampilan ringan untuk suasana santai.',
                'base_price' => 389000,
                'weight_gram' => 420,
                'dimensions' => [null, null, null],
                'size_label' => 'Ukuran',
                'sizes' => ['S', 'M', 'L'],
                'colors' => [['sage', 'Sage', '#A6B49A'], ['black', 'Hitam', '#171717'], ['terracotta', 'Terracotta', '#B86F54']],
                'featured' => true,
                'image' => '/storage/seeders/products/linen-blend-midi-dress.jpg',
                'variants_images' => [
                    'sage' => '/storage/seeders/products/linen-blend-midi-dress-sage.jpg',
                    'black' => '/storage/seeders/products/linen-blend-midi-dress-black.jpg',
                    'terracotta' => '/storage/seeders/products/linen-blend-midi-dress-terracotta.jpg',
                ],
            ],
            [
                'name' => 'Tailored Everyday Blazer',
                'category' => 'outerwear',
                'description' => 'Blazer tailored dengan struktur ringan dan potongan modern, ideal untuk kebutuhan formal maupun smart casual.',
                'base_price' => 549000,
                'weight_gram' => 780,
                'dimensions' => [null, null, null],
                'size_label' => 'Ukuran',
                'sizes' => ['M', 'L', 'XL'],
                'colors' => [['black', 'Hitam', '#171717'], ['stone', 'Stone', '#B9B1A3']],
                'featured' => false,
                'image' => '/storage/seeders/products/tailored-everyday-blazer.jpg',
                'variants_images' => [
                    'black' => '/storage/seeders/products/tailored-everyday-blazer-black.jpg',
                    'stone' => '/storage/seeders/products/tailored-everyday-blazer-stone.jpg',
                ],
            ],
            [
                'name' => 'Canvas Low-Top Sneakers',
                'category' => 'sepatu',
                'description' => 'Sneakers low-top berbahan kanvas dengan outsole fleksibel untuk menemani aktivitas harian.',
                'base_price' => 429000,
                'weight_gram' => 850,
                'dimensions' => [null, null, null],
                'size_label' => 'EU',
                'sizes' => ['39', '40', '41'],
                'colors' => [['white', 'Putih', '#F5F5F4'], ['black', 'Hitam', '#171717'], ['sand', 'Sand', '#C9B99A']],
                'featured' => true,
                'image' => '/storage/seeders/products/canvas-low-top-sneakers.jpg',
                'variants_images' => [
                    'white' => '/storage/seeders/products/canvas-low-top-sneakers-white.jpg',
                    'black' => '/storage/seeders/products/canvas-low-top-sneakers-black.jpg',
                    'sand' => '/storage/seeders/products/canvas-low-top-sneakers-sand.jpg',
                ],
            ],
            [
                'name' => 'Everyday Leather Sneakers',
                'category' => 'sepatu',
                'description' => 'Sneakers kulit sintetis dengan desain bersih dan bantalan empuk untuk gaya kasual modern.',
                'base_price' => 599000,
                'weight_gram' => 920,
                'dimensions' => [null, null, null],
                'size_label' => 'EU',
                'sizes' => ['40', '41', '42'],
                'colors' => [['white', 'Putih', '#F5F5F4'], ['black', 'Hitam', '#171717']],
                'featured' => false,
                'image' => '/storage/seeders/products/everyday-leather-sneakers.jpg',
                'variants_images' => [
                    'white' => '/storage/seeders/products/everyday-leather-sneakers-white.jpg',
                    'black' => '/storage/seeders/products/everyday-leather-sneakers-black.jpg',
                ],
            ],
            [
                'name' => 'Daily Canvas Tote Bag',
                'category' => 'tas',
                'description' => 'Tote bag kanvas berkapasitas lega dengan kompartemen utama untuk kebutuhan harian.',
                'base_price' => 159000,
                'weight_gram' => 350,
                'dimensions' => [null, null, null],
                'size_label' => 'Kapasitas',
                'sizes' => ['Small', 'Regular'],
                'colors' => [['natural', 'Natural', '#D7C6A5'], ['black', 'Hitam', '#171717'], ['olive', 'Olive', '#626B45']],
                'featured' => false,
                'image' => '/storage/seeders/products/daily-canvas-tote-bag.jpg',
                'variants_images' => [
                    'natural' => '/storage/seeders/products/daily-canvas-tote-bag-natural.jpg',
                    'black' => '/storage/seeders/products/daily-canvas-tote-bag-black.jpg',
                    'olive' => '/storage/seeders/products/daily-canvas-tote-bag-olive.jpg',
                ],
            ],
            [
                'name' => 'Urban Everyday Backpack',
                'category' => 'tas',
                'description' => 'Backpack ringan dengan ruang laptop dan saku organizer untuk mobilitas kerja maupun kuliah.',
                'base_price' => 459000,
                'weight_gram' => 780,
                'dimensions' => [null, null, null],
                'size_label' => 'Kapasitas',
                'sizes' => ['18L', '22L'],
                'colors' => [['black', 'Hitam', '#171717'], ['navy', 'Navy', '#1E3A5F'], ['grey', 'Abu-abu', '#929292']],
                'featured' => true,
                'image' => '/storage/seeders/products/urban-everyday-backpack.jpg',
                'variants_images' => [
                    'black' => '/storage/seeders/products/urban-everyday-backpack-black.jpg',
                    'navy' => '/storage/seeders/products/urban-everyday-backpack-navy.jpg',
                    'grey' => '/storage/seeders/products/urban-everyday-backpack-grey.jpg',
                ],
            ],
            [
                'name' => 'Ribbed Long Sleeve Top',
                'category' => 'atasan',
                'description' => 'Atasan lengan panjang bertekstur rib dengan material stretch yang nyaman dan mudah dipadukan.',
                'base_price' => 179000,
                'weight_gram' => 240,
                'dimensions' => [null, null, null],
                'size_label' => 'Ukuran',
                'sizes' => ['S', 'M', 'L'],
                'colors' => [['ivory', 'Ivory', '#EAE3D5'], ['black', 'Hitam', '#171717'], ['mauve', 'Mauve', '#A77C86']],
                'featured' => false,
                'image' => '/storage/seeders/products/ribbed-long-sleeve-top.jpg',
                'variants_images' => [
                    'ivory' => '/storage/seeders/products/ribbed-long-sleeve-top-ivory.jpg',
                    'black' => '/storage/seeders/products/ribbed-long-sleeve-top-black.jpg',
                    'mauve' => '/storage/seeders/products/ribbed-long-sleeve-top-mauve.jpg',
                ],
            ],
            [
                'name' => 'Utility Cargo Trousers',
                'category' => 'bawahan',
                'description' => 'Celana cargo dengan saku fungsional, potongan relaxed, dan bahan ringan untuk gaya urban.',
                'base_price' => 319000,
                'weight_gram' => 590,
                'dimensions' => [null, null, null],
                'size_label' => 'Ukuran',
                'sizes' => ['30', '32', '34'],
                'colors' => [['olive', 'Olive', '#626B45'], ['black', 'Hitam', '#171717']],
                'featured' => true,
                'image' => '/storage/seeders/products/utility-cargo-trousers.jpg',
                'variants_images' => [
                    'olive' => '/storage/seeders/products/utility-cargo-trousers-olive.jpg',
                    'black' => '/storage/seeders/products/utility-cargo-trousers-black.jpg',
                ],
            ],
            [
                'name' => 'Soft Knit Cardigan',
                'category' => 'outerwear',
                'description' => 'Cardigan rajut lembut dengan bukaan kancing depan untuk layering yang praktis dan elegan.',
                'base_price' => 369000,
                'weight_gram' => 510,
                'dimensions' => [null, null, null],
                'size_label' => 'Ukuran',
                'sizes' => ['S', 'M', 'L'],
                'colors' => [['oat', 'Oat', '#D8CDBB'], ['brown', 'Cokelat', '#795548'], ['grey', 'Abu-abu', '#929292']],
                'featured' => false,
                'image' => '/storage/seeders/products/soft-knit-cardigan.jpg',
                'variants_images' => [
                    'oat' => '/storage/seeders/products/soft-knit-cardigan-oat.jpg',
                    'brown' => '/storage/seeders/products/soft-knit-cardigan-brown.jpg',
                    'grey' => '/storage/seeders/products/soft-knit-cardigan-grey.jpg',
                ],
            ],
            [
                'name' => 'Compact Crossbody Bag',
                'category' => 'tas',
                'description' => 'Tas crossbody compact dengan strap adjustable dan kompartemen praktis untuk barang esensial.',
                'base_price' => 289000,
                'weight_gram' => 390,
                'dimensions' => [null, null, null],
                'size_label' => 'Ukuran',
                'sizes' => ['Small', 'Regular'],
                'colors' => [['black', 'Hitam', '#171717'], ['tan', 'Tan', '#B8895B'], ['cream', 'Krem', '#E8DDC8']],
                'featured' => true,
                'image' => '/storage/seeders/products/compact-crossbody-bag.jpg',
                'variants_images' => [
                    'black' => '/storage/seeders/products/compact-crossbody-bag-black.jpg',
                    'tan' => '/storage/seeders/products/compact-crossbody-bag-tan.jpg',
                    'cream' => '/storage/seeders/products/compact-crossbody-bag-cream.jpg',
                ],
            ],
        ];
    }
}
