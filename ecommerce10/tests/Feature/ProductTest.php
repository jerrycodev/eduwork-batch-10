<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));
});

function createProductCategory(): ProductCategory
{
    return ProductCategory::query()->create([
        'name' => 'Audio',
        'slug' => 'audio',
    ]);
}

test('an authenticated user can create a product', function () {
    $category = createProductCategory();

    $response = $this->post(route('products.store'), [
        'name' => 'Wireless Headphones',
        'slug' => 'wireless-headphones',
        'description' => 'Premium wireless headphones.',
        'image' => 'http://localhost:8000/storage/uploads/headphones.png',
        'price' => 3499000,
        'stock' => 10,
        'category_id' => $category->id,
    ]);

    $response->assertRedirect(route('products.index'))
        ->assertSessionHas('success', 'Product created successfully.');

    $this->assertDatabaseHas('products', [
        'slug' => 'wireless-headphones',
        'name' => 'Wireless Headphones',
    ]);
});

test('a product cannot be created without required data', function () {
    $response = $this->post(route('products.store'), []);

    $response->assertSessionHasErrors([
        'name',
        'slug',
        'description',
        'image',
        'price',
        'stock',
        'category_id',
    ]);

    expect(Product::query()->count())->toBe(0);
});

test('a product and its uploaded image can be deleted', function () {
    Storage::fake('public');
    Storage::disk('public')->put('uploads/headphones.png', 'image');

    $product = Product::query()->create([
        'name' => 'Wireless Headphones',
        'slug' => 'wireless-headphones',
        'description' => 'Premium wireless headphones.',
        'image' => Storage::disk('public')->url('uploads/headphones.png'),
        'price' => 3499000,
        'stock' => 10,
        'category_id' => createProductCategory()->id,
    ]);

    $response = $this->delete(route('products.destroy', $product));

    $response->assertRedirect(route('products.index'))
        ->assertSessionHas('success', 'Product deleted successfully.');

    $this->assertModelMissing($product);
    Storage::disk('public')->assertMissing('uploads/headphones.png');
});


test('visiting a product detail page increments its click counter', function () {
    $category = createProductCategory();

    $product = Product::query()->create([
        'name' => 'Click Tracking Product',
        'slug' => 'click-tracking-product',
        'description' => 'Product used to verify click tracking.',
        'image' => 'http://localhost:8000/storage/uploads/product.png',
        'price' => 100000,
        'stock' => 5,
        'category_id' => $category->id,
    ]);

    $this->get(route('product-detail', $product))->assertOk();
    $this->get(route('product-detail', $product))->assertOk();

    expect($product->fresh()->click)->toBe(1);
});

test('viewing another product in the same session increments that product independently', function () {
    $category = createProductCategory();

    $firstProduct = Product::query()->create([
        'name' => 'First Session Product',
        'slug' => 'first-session-product',
        'description' => 'First product for session tracking.',
        'image' => 'http://localhost:8000/storage/uploads/first.png',
        'price' => 100000,
        'stock' => 5,
        'category_id' => $category->id,
    ]);

    $secondProduct = Product::query()->create([
        'name' => 'Second Session Product',
        'slug' => 'second-session-product',
        'description' => 'Second product for session tracking.',
        'image' => 'http://localhost:8000/storage/uploads/second.png',
        'price' => 120000,
        'stock' => 5,
        'category_id' => $category->id,
    ]);

    $this->get(route('product-detail', $firstProduct))->assertOk();
    $this->get(route('product-detail', $secondProduct))->assertOk();
    $this->get(route('product-detail', $firstProduct))->assertOk();

    expect($firstProduct->fresh()->click)->toBe(1)
        ->and($secondProduct->fresh()->click)->toBe(1);
});

test('the edit page previews the current product image', function () {
    $product = Product::query()->create([
        'name' => 'Wireless Headphones',
        'slug' => 'wireless-headphones',
        'description' => 'Premium wireless headphones.',
        'image' => '/storage/uploads/old-headphones.png',
        'price' => 3499000,
        'stock' => 10,
        'category_id' => createProductCategory()->id,
    ]);

    $response = $this->get(route('products.edit', $product));

    $response->assertOk()
        ->assertSee('/storage/uploads/old-headphones.png', false)
        ->assertSee('data-file-delete-existing', false)
        ->assertSee('Current image');
});
