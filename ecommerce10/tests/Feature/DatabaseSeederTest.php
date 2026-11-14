<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds development data idempotently without overwriting product changes', function () {
    $this->seed(DatabaseSeeder::class);

    expect(ProductCategory::query()->count())->toBe(5)
        ->and(Product::query()->count())->toBe(16)
        ->and(User::query()->whereIn('email', [
            'demo1@ecommerce.test',
            'demo2@ecommerce.test',
        ])->count())->toBe(2);

    $product = Product::query()->firstOrFail();
    $product->update(['stock' => 3]);

    $this->seed(DatabaseSeeder::class);

    expect(ProductCategory::query()->count())->toBe(5)
        ->and(Product::query()->count())->toBe(16)
        ->and(User::query()->whereIn('email', [
            'demo1@ecommerce.test',
            'demo2@ecommerce.test',
        ])->count())->toBe(2)
        ->and($product->fresh()->stock)->toBe(3);
});
