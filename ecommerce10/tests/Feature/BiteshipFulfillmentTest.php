<?php

use App\Models\BiteshipWebhookLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\BiteshipShippingService;
use Illuminate\Support\Facades\Http;

function makeBiteshipReadyOrder(User $customer): Order
{
    $category = ProductCategory::query()->create([
        'name' => 'Biteship Test '.uniqid(),
        'slug' => 'biteship-test-'.uniqid(),
    ]);
    $product = Product::query()->create([
        'name' => 'Biteship Test Product',
        'slug' => 'biteship-product-'.uniqid(),
        'description' => 'Test item',
        'image' => 'products/test.jpg',
        'price' => 125000,
        'stock' => 5,
        'weight_gram' => 750,
        'category_id' => $category->id,
    ]);
    $order = Order::query()->create([
        'user_id' => $customer->id,
        'cart_id' => null,
        'user_name' => $customer->name,
        'user_email' => $customer->email,
        'recipient_name' => 'Receiver Test',
        'user_phone' => '08123456789',
        'address' => 'Jalan Test No. 1',
        'city' => 'Jakarta Selatan',
        'state' => 'DKI Jakarta',
        'zip' => '12345',
        'country' => 'Indonesia',
        'total' => 150000,
        'subtotal' => 125000,
        'shipping_cost' => 25000,
        'shipping_courier' => 'JNE',
        'shipping_courier_code' => 'jne',
        'shipping_service' => 'REG',
        'shipping_service_code' => 'reg',
        'status' => 'processing',
        'payment_status' => 'paid',
    ]);
    $order->orderItems()->create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 1,
        'price' => 125000,
        'total' => 125000,
    ]);

    return $order;
}

test('creating a paid processing order books one Biteship shipment with a stable reference', function () {
    config([
        'biteship.api_key' => 'biteship_test.fake-key',
        'biteship.base_url' => 'https://api.biteship.com',
        'biteship.warehouse' => [
            'name' => 'RevStore Warehouse',
            'phone' => '081111111111',
            'email' => 'warehouse@revstore.test',
            'address' => 'Gudang RevStore, Jakarta',
            'postal_code' => '12950',
            'note' => 'Pintu loading dock',
        ],
    ]);
    Http::fake(['api.biteship.com/v1/orders' => Http::response([
        'success' => true,
        'id' => 'biteship-order-001',
        'courier_waybill_id' => 'JNE-001',
        'courier_tracking_id' => 'tracking-001',
        'status' => 'confirmed',
        'price' => 27000,
    ], 200)]);

    $order = makeBiteshipReadyOrder(User::factory()->create());
    $service = app(BiteshipShippingService::class);
    $created = $service->createShipment($order);
    $again = $service->createShipment($order->fresh());

    expect($created->biteship_order_id)->toBe('biteship-order-001')
        ->and($created->biteship_waybill_id)->toBe('JNE-001')
        ->and($created->tracking_number)->toBe('JNE-001')
        ->and($created->biteship_price)->toBe(27000)
        ->and($again->biteship_order_id)->toBe('biteship-order-001');

    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->url() === 'https://api.biteship.com/v1/orders'
        && $request['reference_id'] === $order->order_number
        && $request['courier_company'] === 'jne'
        && $request['courier_type'] === 'reg'
        && $request['items'][0]['weight'] === 750);
});

test('Biteship webhook rejects requests without the configured secret header', function () {
    config(['biteship.webhook.signature_key' => 'X-Biteship-Signature', 'biteship.webhook.signature_secret' => 'test-secret']);

    $this->postJson(route('webhooks.biteship'), ['event' => 'order.status'])
        ->assertUnauthorized();

    $log = BiteshipWebhookLog::query()->latest('received_at')->firstOrFail();
    expect($log->result)->toBe('rejected')
        ->and($log->signature_valid)->toBeFalse()
        ->and($log->http_status)->toBe(401);
});

test('Biteship webhook installation handshake accepts an empty request and responds with plain ok', function () {
    config(['biteship.webhook.signature_key' => 'X-Biteship-Signature', 'biteship.webhook.signature_secret' => 'test-secret']);

    $this->call('POST', route('webhooks.biteship'), [], [], [], ['CONTENT_TYPE' => 'application/json'])
        ->assertOk()
        ->assertSeeText('ok');
});

test('Biteship audit records local blockers before attempting an API request', function () {
    config(['biteship.api_key' => null, 'biteship.warehouse' => [
        'name' => null, 'phone' => null, 'address' => null, 'postal_code' => null,
    ]]);
    $admin = User::factory()->create(['role' => 'admin']);
    $order = makeBiteshipReadyOrder(User::factory()->create());

    $this->actingAs($admin, 'admin')
        ->post(route('biteship-reconciliation.check', $order))
        ->assertRedirect()
        ->assertSessionHas('error');

    $check = $order->biteshipReconciliationChecks()->firstOrFail();
    expect($check->result)->toBe('blocked')
        ->and($check->message)->toContain('BITESHIP_API_KEY')
        ->and($check->message)->toContain('BITESHIP_WAREHOUSE_NAME');
});

test('Biteship audit stores the actual API rejection message on retry', function () {
    config([
        'biteship.api_key' => 'biteship_test.fake-key',
        'biteship.base_url' => 'https://api.biteship.com',
        'biteship.warehouse' => [
            'name' => 'RevStore Warehouse', 'phone' => '081111111111', 'email' => null,
            'address' => 'Gudang RevStore, Jakarta', 'postal_code' => '12950', 'note' => null,
        ],
    ]);
    Http::fake(['api.biteship.com/v1/orders' => Http::response([
        'success' => false,
        'code' => 'INSUFFICIENT_BALANCE',
        'error' => 'Insufficient balance',
        'message' => 'Your Biteship balance is insufficient.',
    ], 422)]);
    $admin = User::factory()->create(['role' => 'admin']);
    $order = makeBiteshipReadyOrder(User::factory()->create());

    $this->actingAs($admin, 'admin')
        ->post(route('biteship-reconciliation.retry', $order))
        ->assertRedirect()
        ->assertSessionHas('error');

    $check = $order->biteshipReconciliationChecks()->firstOrFail();
    expect($check->result)->toBe('error')
        ->and($check->message)->toContain('HTTP 422')
        ->and($check->message)->toContain('Your Biteship balance is insufficient.')
        ->and($check->message)->toContain('INSUFFICIENT_BALANCE')
        ->and($check->http_status)->toBe(422);
    Http::assertSentCount(1);
});

test('admin can open the Biteship audit dashboard', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'admin')
        ->get(route('biteship-reconciliation.index'))
        ->assertOk()
        ->assertSee('Biteship Audit')
        ->assertSee('Pesanan lunas yang belum memiliki shipment')
        ->assertSee('Riwayat pemeriksaan dan percobaan')
        ->assertSee('Webhook masuk dari Biteship')
        ->assertSee('Payload hanya menyimpan field integrasi yang aman.');
});

test('valid Biteship status webhook is recorded with its linked order and safe payload', function () {
    config(['biteship.webhook.signature_key' => 'X-Biteship-Signature', 'biteship.webhook.signature_secret' => 'test-secret']);
    $order = makeBiteshipReadyOrder(User::factory()->create());
    $order->forceFill(['biteship_order_id' => 'bite-webhook-audit-001'])->save();

    $this->postJson(route('webhooks.biteship'), [
        'event' => 'order.status',
        'reference_id' => $order->order_number,
        'order_id' => 'bite-webhook-audit-001',
        'status' => 'picked',
        'courier_waybill_id' => 'JNE-AUDIT-001',
        'destination_contact_phone' => '08123456789',
        'destination_address' => 'Private customer address',
    ], ['X-Biteship-Signature' => 'test-secret'])->assertOk();

    $log = BiteshipWebhookLog::query()->latest('received_at')->firstOrFail();
    expect($log->result)->toBe('processed')
        ->and($log->signature_valid)->toBeTrue()
        ->and($log->http_status)->toBe(200)
        ->and($log->order_id)->toBe($order->id)
        ->and($log->event)->toBe('order.status')
        ->and($log->remote_status)->toBe('picked')
        ->and($log->payload)->toHaveKey('courier_waybill_id')
        ->and($log->payload)->not->toHaveKey('destination_contact_phone')
        ->and($log->payload)->not->toHaveKey('destination_address');
});

test('Biteship price and waybill webhook events are separately recorded and synchronized', function () {
    config(['biteship.webhook.signature_key' => 'X-Biteship-Signature', 'biteship.webhook.signature_secret' => 'test-secret']);
    $order = makeBiteshipReadyOrder(User::factory()->create());
    $order->forceFill(['biteship_order_id' => 'bite-webhook-audit-002'])->save();

    $this->postJson(route('webhooks.biteship'), [
        'event' => 'order.price', 'order_id' => 'bite-webhook-audit-002', 'price' => 31000,
    ], ['X-Biteship-Signature' => 'test-secret'])->assertOk();
    $this->postJson(route('webhooks.biteship'), [
        'event' => 'order.waybill_id', 'order_id' => 'bite-webhook-audit-002', 'courier_waybill_id' => 'JNE-WAYBILL-002',
    ], ['X-Biteship-Signature' => 'test-secret'])->assertOk();

    expect($order->fresh()->biteship_price)->toBe(31000)
        ->and($order->fresh()->tracking_number)->toBe('JNE-WAYBILL-002')
        ->and(BiteshipWebhookLog::query()->where('order_id', $order->id)->where('result', 'processed')->count())->toBe(2)
        ->and(BiteshipWebhookLog::query()->where('event', 'order.price')->exists())->toBeTrue()
        ->and(BiteshipWebhookLog::query()->where('event', 'order.waybill_id')->exists())->toBeTrue();
});

test('signed Biteship webhook with unknown order is recorded as rejected', function () {
    config(['biteship.webhook.signature_key' => 'X-Biteship-Signature', 'biteship.webhook.signature_secret' => 'test-secret']);

    $this->postJson(route('webhooks.biteship'), [
        'event' => 'order.status', 'reference_id' => 'ORD-UNKNOWN-001', 'status' => 'picked',
    ], ['X-Biteship-Signature' => 'test-secret'])->assertNotFound();

    $log = BiteshipWebhookLog::query()->latest('received_at')->firstOrFail();
    expect($log->result)->toBe('rejected')
        ->and($log->signature_valid)->toBeTrue()
        ->and($log->http_status)->toBe(404)
        ->and($log->message)->toContain('No RevStore order matches');
});

test('admin order detail renders tracking history fetched from the Biteship tracking page API', function () {
    config([
        'biteship.base_url' => 'https://api.biteship.com',
        'biteship.tracking_environment' => 'development',
    ]);
    Http::fake([
        'api.biteship.com/v1/trackings/tracking_pages/tracking-page-test?environment=development' => Http::response([
            'success' => true,
            'tracking_history' => [
                [
                    'status' => 'picked',
                    'description' => 'Item has been picked by courier',
                    'updated_at' => '2026-11-16T21:35:00+07:00',
                ],
            ],
        ], 200),
    ]);

    $admin = User::factory()->create(['role' => 'admin']);
    $order = makeBiteshipReadyOrder(User::factory()->create());
    $order->forceFill(['biteship_tracking_id' => 'tracking-page-test'])->save();

    $this->actingAs($admin, 'admin')
        ->get(route('orders.show', $order))
        ->assertOk()
        ->assertSee('Progress Tracking')
        ->assertSee('Item has been picked by courier')
        ->assertSee('16 Nov');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/v1/trackings/tracking_pages/tracking-page-test')
        && $request['environment'] === 'development'
        && $request->hasHeader('Accept', 'application/json, text/plain, */*')
        && $request->hasHeader('Accept-Language', 'en-ID,en-GB;q=0.9,en-US;q=0.8,en;q=0.7')
        && ! $request->hasHeader('If-None-Match')
        && $request->hasHeader('Origin', 'https://track.biteship.com')
        && $request->hasHeader('Referer', 'https://track.biteship.com/')
        && $request->hasHeader('Sec-Fetch-Mode', 'cors')
        && $request->hasHeader('User-Agent', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36'));
});

test('customer order detail displays Biteship tracking history for its own order', function () {
    config([
        'biteship.base_url' => 'https://api.biteship.com',
        'biteship.tracking_environment' => 'development',
    ]);
    Http::fake([
        'api.biteship.com/v1/trackings/tracking_pages/customer-tracking-test?environment=development' => Http::response([
            'success' => true,
            'tracking_history' => [[
                'status' => 'in_transit',
                'description' => 'Paket sedang dalam perjalanan menuju kota tujuan',
                'updated_at' => '2026-11-16T21:35:00+07:00',
            ]],
        ], 200),
    ]);

    $customer = User::factory()->create();
    $order = makeBiteshipReadyOrder($customer);
    $order->forceFill(['biteship_tracking_id' => 'customer-tracking-test'])->save();

    $this->actingAs($customer, 'customer')
        ->get(route('orders.customer.show', $order))
        ->assertOk()
        ->assertSee('Riwayat Perjalanan Paket')
        ->assertSee('Paket sedang dalam perjalanan menuju kota tujuan')
        ->assertSee('customer-tracking-test');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/v1/trackings/tracking_pages/customer-tracking-test')
        && $request['environment'] === 'development');
});

test('Biteship exceptional shipment statuses are retained without falsely cancelling the RevStore order', function (string $status) {
    config(['biteship.webhook.signature_key' => 'X-Biteship-Signature', 'biteship.webhook.signature_secret' => 'test-secret']);
    $order = makeBiteshipReadyOrder(User::factory()->create());
    $order->forceFill(['biteship_order_id' => 'bite-exception-'.$status, 'biteship_status' => 'picking_up'])->save();

    $this->postJson(route('webhooks.biteship'), [
        'event' => 'order.status',
        'order_id' => 'bite-exception-'.$status,
        'status' => $status,
    ], ['X-Biteship-Signature' => 'test-secret'])->assertOk();

    expect($order->fresh()->biteship_status)->toBe($status)
        ->and($order->fresh()->status)->toBe('processing');
})->with([
    'cancelled',
    'on_hold',
    'courier_not_found',
    'rejected',
    'return_in_transit',
    'returned',
    'disposed',
]);

test('Biteship shipment cancellation calls the supported cancel endpoint and records cancelled status', function () {
    config([
        'biteship.api_key' => 'biteship_test.fake-key',
        'biteship.base_url' => 'https://api.biteship.com',
    ]);
    Http::fake([
        'api.biteship.com/v1/orders/bite-cancel-001/cancel' => Http::response([
            'success' => true,
            'messages' => 'Order cancelled successfully',
        ], 200),
    ]);

    $order = makeBiteshipReadyOrder(User::factory()->create());
    $order->forceFill([
        'biteship_order_id' => 'bite-cancel-001',
        'biteship_status' => 'allocated',
    ])->save();

    $cancelled = app(BiteshipShippingService::class)->cancelShipment($order, 'wrong_address');

    expect($cancelled->biteship_status)->toBe('cancelled');
    Http::assertSent(fn ($request) => $request->url() === 'https://api.biteship.com/v1/orders/bite-cancel-001/cancel'
        && $request['cancellation_reason_code'] === 'change_address');
});

test('Biteship shipment cannot be cancelled after courier pickup', function () {
    config(['biteship.api_key' => 'biteship_test.fake-key']);
    Http::fake();

    $order = makeBiteshipReadyOrder(User::factory()->create());
    $order->forceFill([
        'biteship_order_id' => 'bite-cancel-picked',
        'biteship_status' => 'picked',
    ])->save();

    expect(fn () => app(BiteshipShippingService::class)->cancelShipment($order, 'changed_mind'))
        ->toThrow(RuntimeException::class, 'hanya mengizinkan pembatalan sebelum paket dijemput');
    Http::assertNothingSent();
});

test('Biteship on hold status can resume to a normal pickup milestone', function () {
    config(['biteship.webhook.signature_key' => 'X-Biteship-Signature', 'biteship.webhook.signature_secret' => 'test-secret']);
    $order = makeBiteshipReadyOrder(User::factory()->create());
    $order->forceFill(['biteship_order_id' => 'bite-hold-resume-001', 'biteship_status' => 'picking_up'])->save();

    $this->postJson(route('webhooks.biteship'), [
        'event' => 'order.status', 'order_id' => 'bite-hold-resume-001', 'status' => 'on_hold',
    ], ['X-Biteship-Signature' => 'test-secret'])->assertOk();
    expect($order->fresh()->biteship_status)->toBe('on_hold');

    $this->postJson(route('webhooks.biteship'), [
        'event' => 'order.status', 'order_id' => 'bite-hold-resume-001', 'status' => 'picking_up',
    ], ['X-Biteship-Signature' => 'test-secret'])->assertOk();
    expect($order->fresh()->biteship_status)->toBe('picking_up');
});
