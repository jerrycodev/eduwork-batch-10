<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Models\WishlistItem;

function makeWishlistProduct(): Product
{
    $category = ProductCategory::query()->create([
        'name' => 'Wishlist Category',
        'slug' => 'wishlist-category-'.uniqid(),
    ]);

    return Product::query()->create([
        'name' => 'Wishlist Test Product',
        'slug' => 'wishlist-test-product-'.uniqid(),
        'description' => 'Product for wishlist tests.',
        'image' => '/storage/uploads/wishlist-product.png',
        'price' => 99000,
        'stock' => 5,
        'category_id' => $category->id,
    ]);
}

test('authenticated customer can add a product to wishlist from product detail', function () {
    $user = User::factory()->create();
    $product = makeWishlistProduct();

    $this->actingAs($user, 'customer')
        ->get(route('product-detail', $product->slug))
        ->assertOk()
        ->assertSee(route('wishlist.toggle', $product), false)
        ->assertSee('Tambahkan ke wishlist');

    $this->post(route('wishlist.toggle', $product))
        ->assertRedirect()
        ->assertSessionHas('success', 'Produk berhasil ditambahkan ke wishlist.');

    $this->assertDatabaseHas('wishlist_items', [
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);
});

test('wishlist toggle removes an already saved product', function () {
    $user = User::factory()->create();
    $product = makeWishlistProduct();
    $item = WishlistItem::query()->create(['user_id' => $user->id, 'product_id' => $product->id]);

    $this->actingAs($user, 'customer')
        ->post(route('wishlist.toggle', $product))
        ->assertRedirect()
        ->assertSessionHas('success', 'Produk dihapus dari wishlist.');

    $this->assertDatabaseMissing('wishlist_items', ['id' => $item->id]);
});

test('customer can view wishlist and remove an item', function () {
    $user = User::factory()->create();
    $product = makeWishlistProduct();
    $item = WishlistItem::query()->create(['user_id' => $user->id, 'product_id' => $product->id]);

    $this->actingAs($user, 'customer')
        ->get(route('wishlist.index'))
        ->assertOk()
        ->assertSee('Wishlist Saya')
        ->assertSee($product->name)
        ->assertSee(route('wishlist.destroy', $item), false);

    $this->delete(route('wishlist.destroy', $item))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('wishlist_items', ['id' => $item->id]);
});

test('wishlist items belong to their owner and guests must sign in', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $product = makeWishlistProduct();
    $item = WishlistItem::query()->create(['user_id' => $owner->id, 'product_id' => $product->id]);

    $this->delete(route('wishlist.destroy', $item))->assertRedirect(route('login'));

    $this->actingAs($otherUser, 'customer')
        ->delete(route('wishlist.destroy', $item))
        ->assertForbidden();

    $this->assertDatabaseHas('wishlist_items', ['id' => $item->id, 'user_id' => $owner->id]);
});

test('admin cannot use customer wishlist', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $product = makeWishlistProduct();

    $this->actingAs($admin, 'admin')
        ->get(route('wishlist.index'))
        ->assertRedirect(route('login'));

    $this->post(route('wishlist.toggle', $product))->assertRedirect(route('login'));
});
