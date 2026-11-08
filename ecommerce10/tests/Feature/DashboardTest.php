<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

test('the dashboard renders database-backed order and product metrics', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create(['role' => 'user']);
    $now = now();

    $categoryId = DB::table('product_categories')->insertGetId([
        'name' => 'Dashboard Category',
        'slug' => 'dashboard-category',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $productId = DB::table('products')->insertGetId([
        'name' => 'Dashboard Product',
        'slug' => 'dashboard-product',
        'description' => 'Product for dashboard metrics.',
        'image' => '/storage/dashboard-product.png',
        'price' => 150000,
        'stock' => 10,
        'click' => 4,
        'category_id' => $categoryId,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $cartId = DB::table('carts')->insertGetId([
        'user_id' => $customer->id,
        'product_id' => $productId,
        'quantity' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('orders')->insert([
        'user_id' => $customer->id,
        'cart_id' => $cartId,
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
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $this->actingAs($admin);

    $response = $this->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Total Products')
        ->assertSee('Total Product Clicks')
        ->assertSee('Total Categories')
        ->assertSee('Total Orders')
        ->assertSee('Orders This Year')
        ->assertSee('Order Status')
        ->assertSee('Pending')
        ->assertSee('"name":"Pending","value":1', false)
        ->assertSee('dashboard-chart-data', false)
        ->assertSee('orders-chart', false)
        ->assertSee('order-status-chart', false)
        ->assertSee('Products Clicks')
        ->assertSee('Total Clicks')
        ->assertSee('4')
        ->assertSee('action="'.route('logout').'"', false)
        ->assertSee('method="POST"', false)
        ->assertSee('<button type="submit"', false)
        ->assertDontSee('href="'.route('logout').'"', false)
        ->assertDontSee('Operating System');
});
