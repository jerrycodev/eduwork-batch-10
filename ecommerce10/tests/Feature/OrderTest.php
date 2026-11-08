<?php

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;

function makeOrderTestProduct(int $stock = 10): Product
{
    $category = ProductCategory::query()->create([
        'name' => 'Order Category',
        'slug' => 'order-category-' . uniqid(),
    ]);

    return Product::query()->create([
        'name' => 'Order Test Product',
        'slug' => 'order-test-product-' . uniqid(),
        'description' => 'Product for order feature tests.',
        'image' => '/storage/uploads/order-product.png',
        'price' => 125000,
        'stock' => $stock,
        'category_id' => $category->id,
    ]);
}

test('checkout creates an order and order items from the cart then clears the cart and reduces stock', function () {
    $user = User::factory()->create();
    $product = makeOrderTestProduct(stock: 8);
    Cart::query()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $response = $this->actingAs($user)->post(route('checkout.store'));

    $order = Order::query()->firstOrFail();
    $response->assertRedirect(route('orders.customer.show', $order));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'user_id' => $user->id,
        'user_name' => $user->name,
        'user_email' => $user->email,
        'total' => 250000,
        'status' => 'pending',
    ]);
    $this->assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 2,
        'price' => 125000,
        'total' => 250000,
    ]);
    $this->assertDatabaseMissing('carts', ['user_id' => $user->id]);
    $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 6]);
});

test('checkout rejects an empty cart', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('cart'))
        ->post(route('checkout.store'))
        ->assertRedirect(route('cart'))
        ->assertSessionHasErrors('cart');

    $this->assertDatabaseCount('orders', 0);
});

test('checkout rejects insufficient stock without creating an order or clearing the cart', function () {
    $user = User::factory()->create();
    $product = makeOrderTestProduct(stock: 1);
    $cart = Cart::query()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $this->actingAs($user)
        ->from(route('cart'))
        ->post(route('checkout.store'))
        ->assertRedirect(route('cart'))
        ->assertSessionHasErrors('cart');

    $this->assertDatabaseCount('orders', 0);
    $this->assertDatabaseHas('carts', ['id' => $cart->id, 'quantity' => 2]);
    $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 1]);
});

test('customer can read only their own orders', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $order = Order::query()->create([
        'user_id' => $owner->id,
        'cart_id' => null,
        'user_name' => $owner->name,
        'user_email' => $owner->email,
        'total' => 0,
        'status' => 'pending',
    ]);

    $this->actingAs($otherUser)
        ->get(route('orders.customer.index'))
        ->assertOk()
        ->assertDontSee('#' . $order->id);

    $this->get(route('orders.customer.show', $order))->assertForbidden();

    $this->actingAs($owner)
        ->get(route('orders.customer.show', $order))
        ->assertOk()
        ->assertSee('Pesanan #' . $order->id);
});

 test('guest cannot access order pages or create orders', function () {
    $this->get(route('orders.customer.index'))->assertRedirect(route('login'));
    $this->post(route('checkout.store'))->assertRedirect(route('login'));
});


test('admin can view the separate simple orders table', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create(['role' => 'user']);
    $order = Order::query()->create([
        'user_id' => $customer->id,
        'cart_id' => null,
        'user_name' => $customer->name,
        'user_email' => $customer->email,
        'total' => 125000,
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->get(route('orders.index'))
        ->assertOk()
        ->assertSee('Order ID')
        ->assertSee($customer->name)
        ->assertSee('Rp 125.000')
        ->assertSee(route('orders.show', $order));
});

test('customer cannot access admin orders page', function () {
    $customer = User::factory()->create(['role' => 'user']);

    $this->actingAs($customer)
        ->get(route('orders.index'))
        ->assertRedirect(route('home'));
});


test('admin can view a dedicated order detail page', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create(['role' => 'user']);
    $order = Order::query()->create([
        'user_id' => $customer->id,
        'cart_id' => null,
        'user_name' => $customer->name,
        'user_email' => $customer->email,
        'total' => 250000,
        'status' => 'pending',
    ]);
    $product = makeOrderTestProduct();
    $order->orderItems()->create([
        'product_id' => $product->id,
        'product_name' => 'Sample product',
        'quantity' => 2,
        'price' => 125000,
        'total' => 250000,
    ]);

    $this->actingAs($admin)
        ->get(route('orders.show', $order))
        ->assertOk()
        ->assertSee('Detail Order')
        ->assertSee($customer->name)
        ->assertSee('Sample product')
        ->assertSee('Rp 250.000')
        ->assertSee(route('orders.index'));
});
