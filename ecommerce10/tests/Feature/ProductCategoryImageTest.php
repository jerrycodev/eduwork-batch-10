<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('admin can create a category with an image and see it in the category listing', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'admin')->post(route('product-categories.store'), [
        'name' => 'Outdoor',
        'slug' => 'outdoor',
        'image' => UploadedFile::fake()->image('outdoor.jpg'),
    ])->assertRedirect(route('product-categories.index'))
        ->assertSessionHas('success');

    $category = ProductCategory::query()->where('slug', 'outdoor')->firstOrFail();

    expect($category->image)->not->toBeNull();
    Storage::disk('public')->assertExists($category->image);

    $this->actingAs($admin, 'admin')->get(route('product-categories.index'))
        ->assertOk()
        ->assertSee('Outdoor')
        ->assertSee(Storage::disk('public')->url($category->image));
});

test('admin can create a category without manually entering its slug', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'admin')->post(route('product-categories.store'), [
        'name' => 'Perlengkapan Outdoor',
    ])->assertRedirect(route('product-categories.index'))
        ->assertSessionHas('success');

    expect(ProductCategory::query()->where('name', 'Perlengkapan Outdoor')->value('slug'))
        ->toBe('perlengkapan-outdoor');
});

test('admin can replace a category image while keeping the old image when no replacement is uploaded', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);
    $oldPath = UploadedFile::fake()->image('old.jpg')->store('categories', 'public');
    $category = ProductCategory::query()->create([
        'name' => 'Fashion',
        'slug' => 'fashion',
        'image' => $oldPath,
    ]);

    $this->actingAs($admin, 'admin')->put(route('product-categories.update', $category), [
        'name' => 'Fashion',
        'slug' => 'fashion',
    ])->assertRedirect()->assertSessionHas('success');

    expect($category->fresh()->image)->toBe($oldPath);
    Storage::disk('public')->assertExists($oldPath);

    $this->actingAs($admin, 'admin')->put(route('product-categories.update', $category), [
        'name' => 'Fashion',
        'slug' => 'fashion',
        'image' => UploadedFile::fake()->image('new.webp'),
    ])->assertRedirect()->assertSessionHas('success');

    $newPath = $category->fresh()->image;
    expect($newPath)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($newPath);
});

test('admin can delete a category after its products have been soft deleted', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $category = ProductCategory::query()->create([
        'name' => 'Archived Category',
        'slug' => 'archived-category',
    ]);
    $product = Product::query()->create([
        'name' => 'Archived Product',
        'slug' => 'archived-product',
        'description' => 'Product retained in trash.',
        'price' => 25000,
        'stock' => 0,
        'weight_gram' => 300,
        'category_id' => $category->id,
    ]);
    $product->delete();

    $this->actingAs($admin, 'admin')->delete(route('product-categories.destroy', $category))
        ->assertRedirect(route('product-categories.index'))
        ->assertSessionHas('success', 'Category deleted successfully.');

    expect(ProductCategory::query()->whereKey($category->id)->exists())->toBeFalse()
        ->and($product->fresh()->category_id)->toBeNull()
        ->and(Product::withTrashed()->whereKey($product->id)->exists())->toBeTrue();
});

test('category deletion is rejected while active products still use it', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $category = ProductCategory::query()->create([
        'name' => 'Active Category',
        'slug' => 'active-category',
    ]);
    Product::query()->create([
        'name' => 'Active Product',
        'slug' => 'active-product',
        'description' => 'Product still using the category.',
        'price' => 25000,
        'stock' => 0,
        'weight_gram' => 300,
        'category_id' => $category->id,
    ]);

    $this->actingAs($admin, 'admin')->delete(route('product-categories.destroy', $category))
        ->assertRedirect()
        ->assertSessionHasErrors(['category' => 'Kategori tidak dapat dihapus karena masih digunakan oleh produk.']);

    expect(ProductCategory::query()->whereKey($category->id)->exists())->toBeTrue();
});

test('category images reject unsupported files', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'admin')->post(route('product-categories.store'), [
        'name' => 'Invalid',
        'slug' => 'invalid',
        'image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
    ])->assertSessionHasErrors([
        'image' => 'Format gambar harus JPG, PNG, atau WebP.',
    ]);

    expect(ProductCategory::query()->where('slug', 'invalid')->exists())->toBeFalse();
});
