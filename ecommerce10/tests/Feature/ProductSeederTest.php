<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Database\Seeders\ProductSeeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

test('product seeder stores variant images through product images and can be rerun', function () {
    $this->seed(ProductSeeder::class);

    $product = Product::query()->where('slug', 'essential-cotton-t-shirt')->firstOrFail();

    expect(ProductCategory::query()->where('slug', 'atasan')->exists())->toBeTrue()
        ->and($product->variants()->count())->toBe(9)
        ->and($product->images()->count())->toBe(1)
        ->and(ProductImage::query()->where('product_id', $product->id)->whereNotNull('product_variant_id')->count())->toBe(9)
        ->and(Schema::hasColumn('product_variants', 'image'))->toBeFalse();

    Storage::disk('public')->assertExists([
        'seeders/categories/atasan.jpg',
        'seeders/categories/bawahan.jpg',
        'seeders/categories/outerwear.jpg',
        'seeders/categories/dress.jpg',
        'seeders/categories/sepatu.jpg',
        'seeders/categories/tas.jpg',
        'seeders/products/essential-cotton-t-shirt.jpg',
        'seeders/products/essential-cotton-t-shirt-black.jpg',
        'seeders/products/essential-cotton-t-shirt-white.jpg',
        'seeders/products/essential-cotton-t-shirt-navy.jpg',
    ]);

    $variant = $product->variants()->where('is_default', true)->firstOrFail();
    $variant->update(['stock' => 2]);

    $this->seed(ProductSeeder::class);
    $reseededProduct = Product::query()->where('slug', 'essential-cotton-t-shirt')->firstOrFail();

    expect(Product::query()->where('slug', 'essential-cotton-t-shirt')->count())->toBe(1)
        ->and(ProductVariant::query()->count())->toBe(123)
        ->and(ProductImage::query()->where('product_id', $reseededProduct->id)->whereNotNull('product_variant_id')->count())->toBe(9)
        ->and($variant->fresh()->stock)->toBe(2);
});
