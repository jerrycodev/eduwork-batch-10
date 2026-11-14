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
        'weight_gram' => 750,
        'category_id' => $category->id,
    ]);

    $response->assertRedirect(route('products.index'))
        ->assertSessionHas('success', 'Product created successfully.');

    $product = Product::query()->where('slug', 'wireless-headphones')->firstOrFail();
    $this->assertDatabaseHas('products', [
        'slug' => 'wireless-headphones',
        'name' => 'Wireless Headphones',
        'stock' => 10,
    ]);
    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'type' => 'in',
        'quantity' => 10,
        'stock_before' => 0,
        'stock_after' => 10,
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
        'weight_gram',
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
        'weight_gram' => 750,
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
        'weight_gram' => 750,
        'category_id' => createProductCategory()->id,
    ]);

    $response = $this->get(route('products.edit', $product));

    $response->assertOk()
        ->assertSee('/storage/uploads/old-headphones.png', false)
        ->assertSee('data-file-delete-existing', false)
        ->assertSee('Current image');
});

test('editing product stock creates an adjustment movement', function () {
    $category = createProductCategory();
    $product = Product::query()->create([
        'name' => 'Stock Edit Product',
        'slug' => 'stock-edit-product',
        'description' => 'Product used to test stock adjustment through edit.',
        'image' => 'https://example.test/product.png',
        'price' => 100000,
        'stock' => 5,
        'weight_gram' => 500,
        'category_id' => $category->id,
    ]);

    $this->put(route('products.update', $product), [
        'name' => $product->name,
        'slug' => $product->slug,
        'description' => $product->description,
        'image' => $product->image,
        'price' => $product->price,
        'stock' => 9,
        'weight_gram' => $product->weight_gram,
        'category_id' => $category->id,
    ])->assertRedirect(route('products.index'));

    expect($product->fresh()->stock)->toBe(9);
    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'type' => 'adjustment',
        'quantity' => 4,
        'stock_before' => 5,
        'stock_after' => 9,
    ]);
});

test('product detail shows available products from the same category before reviews', function () {
    $category = createProductCategory();
    $otherCategory = ProductCategory::query()->create([
        'name' => 'Fashion',
        'slug' => 'fashion',
    ]);

    $product = Product::query()->create([
        'name' => 'Current Product',
        'slug' => 'current-product',
        'description' => 'Current product description.',
        'image' => 'https://example.test/current.png',
        'price' => 100000,
        'stock' => 5,
        'weight_gram' => 500,
        'category_id' => $category->id,
    ]);

    $relatedProduct = Product::query()->create([
        'name' => 'Related Audio Product',
        'slug' => 'related-audio-product',
        'description' => 'Related product in the same category.',
        'image' => 'https://example.test/related.png',
        'price' => 150000,
        'stock' => 3,
        'weight_gram' => 500,
        'category_id' => $category->id,
    ]);

    Product::query()->create([
        'name' => 'Different Category Product',
        'slug' => 'different-category-product',
        'description' => 'Product from another category.',
        'image' => 'https://example.test/different.png',
        'price' => 200000,
        'stock' => 4,
        'weight_gram' => 500,
        'category_id' => $otherCategory->id,
    ]);

    Product::query()->create([
        'name' => 'Unavailable Audio Product',
        'slug' => 'unavailable-audio-product',
        'description' => 'Out of stock product in the same category.',
        'image' => 'https://example.test/unavailable.png',
        'price' => 90000,
        'stock' => 0,
        'weight_gram' => 500,
        'category_id' => $category->id,
    ]);

    $response = $this->get(route('product-detail', $product));

    $response->assertOk()
        ->assertSee('Produk Terkait')
        ->assertSee('Related Audio Product')
        ->assertDontSee('Different Category Product')
        ->assertDontSee('Unavailable Audio Product');

    $content = $response->getContent();
    expect(strpos($content, 'id="related-products"'))
        ->toBeLessThan(strpos($content, 'id="reviews"'))
        ->and($content)->toContain(route('product-detail', $relatedProduct));
});
