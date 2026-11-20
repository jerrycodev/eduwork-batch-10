<?php

use App\Mail\OrderTransactionalMail;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\MidtransPaymentService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

function makeOrderTestProduct(int $stock = 10): Product
{
    $category = ProductCategory::query()->create([
        'name' => 'Order Category',
        'slug' => 'order-category-'.uniqid(),
    ]);

    return Product::query()->create([
        'name' => 'Order Test Product',
        'slug' => 'order-test-product-'.uniqid(),
        'description' => 'Product for order feature tests.',
        'image' => '/storage/uploads/order-product.png',
        'price' => 125000,
        'stock' => $stock,
        'category_id' => $category->id,
    ]);
}

test('checkout creates an order and order items from the cart then clears the cart and reduces stock', function () {
    Mail::fake();
    $user = User::factory()->create();
    $product = makeOrderTestProduct(stock: 8);
    Cart::query()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $address = $user->addresses()->create(addressPayload(['is_default' => true]));
    $quote = checkoutShippingQuote($this, $user, $address);
    $response = $this->post(route('checkout.store'), checkoutShippingPayload($quote));

    $order = Order::query()->firstOrFail();
    $response->assertRedirect(route('orders.customer.show', $order));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'user_id' => $user->id,
        'user_name' => $user->name,
        'user_email' => $user->email,
        'total' => 265000,
        'subtotal' => 250000,
        'shipping_cost' => 15000,
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
    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'type' => 'out',
        'quantity' => 2,
        'stock_before' => 8,
        'stock_after' => 6,
        'reference_type' => Order::class,
        'reference_id' => $order->id,
    ]);

    Mail::assertQueued(OrderTransactionalMail::class, fn (OrderTransactionalMail $mail): bool => $mail->order->is($order) && $mail->type === 'order_created'
    );
});

test('checkout requests live Biteship rates using warehouse and destination postal codes and product weight', function () {
    $user = User::factory()->create();
    $address = $user->addresses()->create(addressPayload(['postal_code' => '16515', 'is_default' => true]));
    $product = makeOrderTestProduct();
    $product->update(['weight_gram' => 850]);
    Cart::query()->create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 2]);

    $quote = checkoutShippingQuote($this, $user, $address);

    expect($quote['rates'][0]['price'])->toBe(15000)
        ->and($quote['rates'][0]['courier_code'])->toBe('jne');

    Http::assertSent(fn ($request) => $request->url() === 'https://api.biteship.com/v1/rates/couriers' &&
        $request['origin_postal_code'] === '16431' &&
        $request['destination_postal_code'] === '16515' &&
        $request['items'][0]['weight'] === 850 &&
        $request['items'][0]['quantity'] === 2
    );
});

test('checkout rejects a forged shipping rate key without creating an order', function () {
    $user = User::factory()->create();
    $address = $user->addresses()->create(addressPayload(['is_default' => true]));
    $product = makeOrderTestProduct();
    Cart::query()->create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 1]);
    $quote = checkoutShippingQuote($this, $user, $address);
    $payload = checkoutShippingPayload($quote);
    $payload['shipping_rate_key'] = hash('sha256', 'fake|rate|1');

    $this->from(route('cart'))->post(route('checkout.store'), $payload)
        ->assertRedirect(route('cart'))
        ->assertSessionHasErrors('shipping_rate_key');

    $this->assertDatabaseCount('orders', 0);
    $this->assertDatabaseHas('carts', ['user_id' => $user->id]);
});

test('checkout rejects an empty cart', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'customer')
        ->from(route('cart'))
        ->post(route('checkout.store'), [])
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

    $address = $user->addresses()->create(addressPayload(['is_default' => true]));
    $quote = checkoutShippingQuote($this, $user, $address);
    $this->from(route('cart'))
        ->post(route('checkout.store'), checkoutShippingPayload($quote))
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

    $this->actingAs($otherUser, 'customer')
        ->get(route('orders.customer.index'))
        ->assertOk()
        ->assertDontSee($order->order_number);

    $this->get(route('orders.customer.show', $order))->assertForbidden();

    $this->actingAs($owner, 'customer')
        ->get(route('orders.customer.show', $order))
        ->assertOk()
        ->assertSee($order->order_number);
});

test('guest cannot access order pages or create orders', function () {
    $this->get(route('orders.customer.index'))->assertRedirect(route('login'));
    $this->post(route('checkout.store'))->assertRedirect(route('login'));
});

test('admin can view the separate simple orders table', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create(['role' => 'customer']);
    $order = Order::query()->create([
        'user_id' => $customer->id,
        'cart_id' => null,
        'user_name' => $customer->name,
        'user_email' => $customer->email,
        'total' => 125000,
        'status' => 'pending',
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('orders.index'))
        ->assertOk()
        ->assertSee('Order ID')
        ->assertSee($customer->name)
        ->assertSee('Rp 125.000')
        ->assertSee(route('orders.show', $order));
});

test('customer cannot access admin orders page', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    $this->actingAs($customer, 'customer')
        ->get(route('orders.index'))
        ->assertRedirect(route('admin.login'));
});

test('admin can view a dedicated order detail page', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create(['role' => 'customer']);
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

    $this->actingAs($admin, 'admin')
        ->get(route('orders.show', $order))
        ->assertOk()
        ->assertSee('Detail Order')
        ->assertSee($customer->name)
        ->assertSee('Sample product')
        ->assertSee('Rp 250.000')
        ->assertSee(route('orders.index'));
});

test('checkout prepares a Midtrans payment for the newly created order', function () {
    $user = User::factory()->create();
    $product = makeOrderTestProduct(stock: 5);
    Cart::query()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $gateway = Mockery::mock(MidtransPaymentService::class);
    $gateway->shouldReceive('createPayment')->once()->andReturnUsing(function (Order $order) {
        $order->forceFill([
            'payment_order_id' => 'PAY-'.$order->order_number.'-test',
            'snap_token' => 'snap-token-test',
            'snap_redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/test',
            'payment_status' => 'pending',
        ])->save();

        return $order->refresh();
    });
    $this->app->instance(MidtransPaymentService::class, $gateway);

    $address = $user->addresses()->create(addressPayload(['is_default' => true]));
    $quote = checkoutShippingQuote($this, $user, $address);
    $response = $this->post(route('checkout.store'), checkoutShippingPayload($quote));

    $order = Order::query()->firstOrFail();
    $response->assertRedirect(route('orders.customer.show', $order));
    expect($order->order_number)->toMatch('/^ORD-\d{8}-[A-Z0-9]{6}$/')
        ->and($order->payment_order_id)->toBe('PAY-'.$order->order_number.'-test')
        ->and($order->snap_token)->toBe('snap-token-test')
        ->and($order->payment_status)->toBe('pending');
});

test('customer can download their invoice PDF but cannot download another customers invoice', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $order = Order::query()->create([
        'user_id' => $owner->id,
        'cart_id' => null,
        'user_name' => $owner->name,
        'user_email' => $owner->email,
        'total' => 150000,
        'subtotal' => 125000,
        'shipping_cost' => 25000,
        'status' => 'pending',
        'payment_status' => 'pending',
    ]);
    $product = makeOrderTestProduct();
    $order->orderItems()->create([
        'product_id' => $product->id,
        'product_name' => 'PDF test product',
        'quantity' => 1,
        'price' => 125000,
        'total' => 125000,
    ]);

    $this->actingAs($other, 'customer')->get(route('orders.invoice', $order))->assertForbidden();

    $response = $this->actingAs($owner, 'customer')->get(route('orders.invoice', $order))->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf')
        ->and($response->getContent())->toStartWith('%PDF');
});

test('order-created email contains an order link and a PDF invoice attachment', function () {
    $customer = User::factory()->create();
    $order = Order::query()->create([
        'user_id' => $customer->id,
        'cart_id' => null,
        'user_name' => $customer->name,
        'user_email' => $customer->email,
        'total' => 125000,
        'subtotal' => 125000,
        'shipping_cost' => 0,
        'status' => 'pending',
        'payment_status' => 'pending',
    ]);

    $mail = new OrderTransactionalMail($order, 'order_created');

    $attachment = $mail->attachments()[0];
    $pdfBytes = $attachment->attachWith(
        fn ($path) => file_get_contents($path),
        fn ($data) => $data(),
    );

    expect($mail->envelope()->subject)->toContain($order->order_number)
        ->and($attachment->as)->toBe('invoice-'.$order->order_number.'.pdf')
        ->and($attachment->mime)->toBe('application/pdf')
        ->and($pdfBytes)->toStartWith('%PDF')
        ->and($mail->render())->toContain('Lanjutkan Pembayaran')
        ->and($mail->render())->toContain($order->order_number);
});

test('checkout reuses cached Biteship rates for identical payloads and separates changed cart quantities', function () {
    config([
        'biteship.api_key' => 'biteship_test.fake-key',
        'biteship.warehouse.postal_code' => '16431',
        'biteship.couriers' => 'jne,sicepat,jnt',
        'biteship.rates_cache_ttl' => 300,
    ]);
    Http::fake([
        'api.biteship.com/v1/rates/couriers' => Http::response([
            'success' => true,
            'pricing' => [[
                'courier_code' => 'jne',
                'courier_name' => 'JNE',
                'courier_service_code' => 'REG',
                'courier_service_name' => 'Regular',
                'price' => 15000,
                'duration' => '2 - 3 days',
            ]],
        ], 200),
    ]);

    $user = User::factory()->create();
    $address = $user->addresses()->create(addressPayload(['postal_code' => '16515', 'is_default' => true]));
    $product = makeOrderTestProduct();
    $cart = Cart::query()->create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 1]);

    $first = $this->actingAs($user, 'customer')
        ->postJson(route('checkout.shipping-rates'), ['address_id' => $address->id])
        ->assertOk();
    $second = $this->postJson(route('checkout.shipping-rates'), ['address_id' => $address->id])
        ->assertOk();

    expect($second->json('rates')[0]['price'])->toBe($first->json('rates')[0]['price']);
    Http::assertSentCount(1);

    $cart->update(['quantity' => 2]);
    $this->postJson(route('checkout.shipping-rates'), ['address_id' => $address->id])->assertOk();
    $this->postJson(route('checkout.shipping-rates'), ['address_id' => $address->id])->assertOk();

    Http::assertSentCount(2);
});
