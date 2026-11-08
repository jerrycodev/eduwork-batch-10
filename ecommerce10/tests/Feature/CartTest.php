<?php

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;

function makeCartProduct(int $stock = 10): Product
{
    $category = ProductCategory::query()->create([
        'name' => 'Cart Category',
        'slug' => 'cart-category',
    ]);

    return Product::query()->create([
        'name' => 'Cart Test Product',
        'slug' => 'cart-test-product-' . uniqid(),
        'description' => 'Product for cart feature tests.',
        'image' => '/storage/uploads/cart-product.png',
        'price' => 125000,
        'stock' => $stock,
        'category_id' => $category->id,
    ]);
}

test('guest can view an empty cart page', function () {
    $this->get(route('cart'))
        ->assertOk()
        ->assertSee('Keranjang belanja masih kosong');
});

test('authenticated user can add a product and repeated additions increase its quantity', function () {
    $user = User::factory()->create();
    $product = makeCartProduct();

    $this->actingAs($user)
        ->post(route('cart.store', $product), ['quantity' => 2])
        ->assertRedirect(route('cart'))
        ->assertSessionHas('success');

    $this->post(route('cart.store', $product), ['quantity' => 3])
        ->assertRedirect(route('cart'));

    $this->assertDatabaseHas('carts', [
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 5,
    ]);

    $this->get(route('cart'))
        ->assertOk()
        ->assertSee('Cart Test Product')
        ->assertSee('Rp 625.000');
});

test('adding a quantity beyond available stock is rejected', function () {
    $user = User::factory()->create();
    $product = makeCartProduct(stock: 3);

    $this->actingAs($user)
        ->post(route('cart.store', $product), ['quantity' => 4])
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->assertDatabaseMissing('carts', [
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);
});

test('user can update and remove their own cart item', function () {
    $user = User::factory()->create();
    $product = makeCartProduct();
    $cart = Cart::query()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->patch(route('cart.update', $cart), ['quantity' => 4])
        ->assertRedirect(route('cart'));

    $this->assertDatabaseHas('carts', [
        'id' => $cart->id,
        'quantity' => 4,
    ]);

    $this->delete(route('cart.destroy', $cart))
        ->assertRedirect(route('cart'));

    $this->assertDatabaseMissing('carts', ['id' => $cart->id]);
});

test('user cannot update or remove another users cart item', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $product = makeCartProduct();
    $cart = Cart::query()->create([
        'user_id' => $owner->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $this->actingAs($otherUser)
        ->patch(route('cart.update', $cart), ['quantity' => 2])
        ->assertForbidden();

    $this->delete(route('cart.destroy', $cart))
        ->assertForbidden();

    $this->assertDatabaseHas('carts', [
        'id' => $cart->id,
        'user_id' => $owner->id,
        'quantity' => 1,
    ]);
});

test('guest is redirected to login when adding a product', function () {
    $product = makeCartProduct();

    $this->post(route('cart.store', $product), ['quantity' => 1])
        ->assertRedirect(route('login', ['redirect_to' => route('product-detail', $product, absolute: false)]))
        ->assertSessionHas('error');

    $this->assertDatabaseCount('carts', 0);
});
