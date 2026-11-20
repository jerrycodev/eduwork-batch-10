<?php

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

test('category images reject unsupported files', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'admin')->post(route('product-categories.store'), [
        'name' => 'Invalid',
        'slug' => 'invalid',
        'image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
    ])->assertSessionHasErrors('image');

    expect(ProductCategory::query()->where('slug', 'invalid')->exists())->toBeFalse();
});
