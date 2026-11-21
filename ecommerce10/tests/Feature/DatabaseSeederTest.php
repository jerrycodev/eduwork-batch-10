<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds development data idempotently without duplicating catalog data', function () {
    $this->seed(DatabaseSeeder::class);

    expect(ProductCategory::query()->count())->toBe(6)
        ->and(Product::query()->count())->toBe(16)
        ->and(User::query()->whereIn('email', [
            'demo1@ecommerce.test',
            'demo2@ecommerce.test',
        ])->count())->toBe(2);

    $variantCount = ProductVariant::query()->count();

    $this->seed(DatabaseSeeder::class);
    $product = Product::query()->firstOrFail();

    expect(ProductCategory::query()->count())->toBe(6)
        ->and(Product::query()->count())->toBe(16)
        ->and(User::query()->whereIn('email', [
            'demo1@ecommerce.test',
            'demo2@ecommerce.test',
        ])->count())->toBe(2)
        ->and(ProductVariant::query()->count())->toBe($variantCount)
        ->and($product->stock)->toBe($product->variants()->sum('stock'));
});
