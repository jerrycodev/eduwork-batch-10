<?php

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
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

    $paidOrder = $order->replicate();
    $paidOrder->order_number = 'ORD-'.$now->format('Ymd').'-DASHP1';
    $paidOrder->status = 'processing';
    $paidOrder->payment_status = 'paid';
    $paidOrder->paid_at = $now;
    $paidOrder->total = 300000;
    $paidOrder->save();

    OrderItem::query()->create([
        'order_id' => $paidOrder->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 2,
        'price' => 150000,
        'total' => 300000,
    ]);

    // Add records outside the selected year to verify that order analytics respect the filter,
    // while catalog totals remain lifetime counts.
    $olderCategory = ProductCategory::query()->create([
        'name' => 'Older Dashboard Category',
        'slug' => 'older-dashboard-category',
    ]);
    $olderCategory->forceFill([
        'created_at' => $now->copy()->subYear(),
        'updated_at' => $now->copy()->subYear(),
    ])->save();

    $olderProduct = Product::query()->create([
        'name' => 'Older Dashboard Product',
        'slug' => 'older-dashboard-product',
        'description' => 'Older product for dashboard metrics.',
        'image' => '/storage/older-dashboard-product.png',
        'price' => 100000,
        'stock' => 5,
        'click' => 2,
        'category_id' => $olderCategory->id,
    ]);
    $olderProduct->forceFill([
        'created_at' => $now->copy()->subYear(),
        'updated_at' => $now->copy()->subYear(),
    ])->save();

    $olderOrder = $order->replicate();
    $olderOrder->order_number = 'ORD-'.$now->copy()->subYear()->format('Ymd').'-DASH02';
    $olderOrder->created_at = $now->copy()->subYear();
    $olderOrder->updated_at = $now->copy()->subYear();
    $olderOrder->save();

    $this->actingAs($admin, 'admin');

    $response = $this->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Total Penjualan')
        ->assertSee('Pesanan Dibayar')
        ->assertSee('Total Customer')
        ->assertSee('Produk Aktif')
        ->assertSee('Menunggu Pembayaran')
        ->assertSee('Dalam Pengiriman')
        ->assertSee('Total Pesanan')
        ->assertSee('Customer Baru')
        ->assertSee('Admin')
        ->assertSee('Kategori')
        ->assertSee('Klik Produk')
        ->assertSee('Produk Terlaris')
        ->assertSee('Peringatan Stok')
        ->assertSee('Aktivitas Operasional')
        ->assertSee('Rp 300.000')
        ->assertSee('Filter Dashboard')
        ->assertSee('vs periode sebelumnya')
        ->assertSee('Tren Penjualan')
        ->assertSee('Status Pesanan')
        ->assertSee('Pending')
        ->assertSee('"name":"Pending","value":1', false)
        ->assertSee('dashboard-chart-data', false)
        ->assertSee('sales-chart', false)
        ->assertSee('order-status-chart', false)
        ->assertSee('Pesanan Terbaru')
        ->assertSee('ORD-'.$now->format('Ymd').'-DASH01')
        ->assertSee('ORD-'.$now->format('Ymd').'-DASHP1')
        ->assertSee('Lihat detail')
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
        ->assertSee('Penjualan per jam')
        ->assertSee('Total Penjualan')
        ->assertSee('ORD-'.$now->format('Ymd').'-DASH01')
        ->assertSee('"labels":["00:00","01:00"', false);

    $this->get(route('dashboard', ['period' => '7days']))
        ->assertOk()
        ->assertSee('7 hari terakhir')
        ->assertSee('sales-chart', false);

    $this->get(route('dashboard', ['period' => '30days']))
        ->assertOk()
        ->assertSee('30 hari terakhir')
        ->assertSee('sales-chart', false);

    $this->get(route('dashboard', ['period' => 'month', 'date' => $now->format('Y-m')]))
        ->assertOk()
        ->assertSee('Penjualan harian')
        ->assertSee('"labels":["1","2","3"', false);

    $this->get(route('dashboard', ['period' => 'year', 'date' => $now->format('Y')]))
        ->assertOk()
        ->assertSee('Tren Penjualan')
        ->assertDontSee($olderOrder->order_number);

    $previousYearResponse = $this->get(route('dashboard', [
        'period' => 'year',
        'date' => $now->copy()->subYear()->format('Y'),
    ]));

    $previousYearResponse->assertOk()
        ->assertSee($olderOrder->order_number)
        ->assertDontSee($order->order_number)
        ->assertSee('Tren Penjualan');
});
