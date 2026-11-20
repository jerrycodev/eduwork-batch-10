<?php

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;

test('the dashboard renders database-backed order and product metrics', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create(['role' => 'customer']);
    $now = now();

    $category = ProductCategory::query()->create([
        'name' => 'Dashboard Category',
        'slug' => 'dashboard-category',
    ]);

    $product = Product::query()->create([
        'name' => 'Dashboard Product',
        'slug' => 'dashboard-product',
        'description' => 'Product for dashboard metrics.',
        'image' => '/storage/dashboard-product.png',
        'price' => 150000,
        'stock' => 10,
        'click' => 4,
        'category_id' => $category->id,
    ]);

    $cart = Cart::query()->create([
        'user_id' => $customer->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $order = Order::query()->create([
        'order_number' => 'ORD-'.$now->format('Ymd').'-DASH01',
        'user_id' => $customer->id,
        'cart_id' => $cart->id,
        'user_name' => $customer->name,
        'user_email' => $customer->email,
        'user_phone' => '08123456789',
        'address' => 'Test Street',
        'city' => 'Jakarta',
        'state' => 'DKI Jakarta',
        'zip' => '12345',
        'country' => 'Indonesia',
        'total' => 150000,
        'status' => 'pending',
    ]);

    $this->actingAs($admin, 'admin');

    $response = $this->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Products Created')
        ->assertSee('Product Clicks (All Time)')
        ->assertSee('Categories Created')
        ->assertSee('Orders')
        ->assertSee('Filter Dashboard')
        ->assertSee('Orders This Year')
        ->assertSee('Order Status')
        ->assertSee('Pending')
        ->assertSee('"name":"Pending","value":1', false)
        ->assertSee('dashboard-chart-data', false)
        ->assertSee('orders-chart', false)
        ->assertSee('order-status-chart', false)
        ->assertSee('Latest Orders')
        ->assertSee('ORD-'.$now->format('Ymd').'-DASH01')
        ->assertSee('View details')
        ->assertSee(route('orders.show', $order), false)
        ->assertDontSee('Products Clicks')
        ->assertDontSee('Total Clicks')
        ->assertSee('action="'.route('admin.logout').'"', false)
        ->assertSee('method="POST"', false)
        ->assertSee('<button type="submit"', false)
        ->assertDontSee('href="'.route('admin.logout').'"', false)
        ->assertDontSee('Operating System');

    $this->get(route('dashboard', ['period' => 'day', 'date' => $now->format('Y-m-d')]))
        ->assertOk()
        ->assertSee('Orders Today')
        ->assertSee('Product Clicks (All Time)')
        ->assertSee('ORD-'.$now->format('Ymd').'-DASH01')
        ->assertSee('"labels":["00:00","01:00"', false);

    $this->get(route('dashboard', ['period' => 'month', 'date' => $now->format('Y-m')]))
        ->assertOk()
        ->assertSee('Orders This Month')
        ->assertSee('"labels":["1","2","3"', false);

    $this->get(route('dashboard', ['period' => 'year', 'date' => $now->format('Y')]))
        ->assertOk()
        ->assertSee('Orders This Year');
});
