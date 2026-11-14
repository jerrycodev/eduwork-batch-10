<?php

use App\Events\OrderStatusUpdated;
use App\Mail\OrderTransactionalMail;
use App\Models\Order;
use App\Models\OrderCancellationRequest;
use App\Models\OrderItem;
use App\Models\OrderReturnRequest;
use App\Models\OrderReturnRevision;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Models\User;
use App\Notifications\OrderAlert;
use App\Services\MidtransPaymentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

function makeFulfillmentOrder(User $owner, array $attributes = []): Order
{
    return Order::query()->create(array_merge([
        'user_id' => $owner->id,
        'cart_id' => null,
        'user_name' => $owner->name,
        'user_email' => $owner->email,
        'total' => 150000,
        'status' => 'pending',
        'payment_status' => 'paid',
    ], $attributes));
}

test('admin must add tracking number before marking order shipped', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create();
    $order = makeFulfillmentOrder($customer, ['status' => 'processing']);

    $this->actingAs($admin)
        ->patch(route('orders.status.update', $order), ['status' => 'shipped'])
        ->assertSessionHasErrors('tracking_number');

    $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'processing']);

    $this->patch(route('orders.status.update', $order), [
        'status' => 'shipped',
        'tracking_number' => 'JNE123456789',
    ])->assertRedirect(route('orders.show', $order));

    expect($order->fresh()->status)->toBe('shipped')
        ->and($order->fresh()->tracking_number)->toBe('JNE123456789')
        ->and($order->fresh()->shipped_at)->not->toBeNull();
});

test('admin cancellation restores stock in the same order transition and does not restore it twice', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create();
    $category = ProductCategory::query()->create([
        'name' => 'Fulfillment Cancellation Category',
        'slug' => 'fulfillment-cancel-'.uniqid(),
    ]);
    $product = Product::query()->create([
        'name' => 'Fulfillment Cancellation Product',
        'slug' => 'fulfillment-cancel-product-'.uniqid(),
        'description' => 'Product used for fulfillment cancellation tests.',
        'image' => 'products/test.jpg',
        'price' => 150000,
        'stock' => 2,
        'category_id' => $category->id,
    ]);
    $order = makeFulfillmentOrder($customer, ['payment_status' => 'pending']);
    $order->orderItems()->create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 1,
        'price' => 150000,
        'total' => 150000,
    ]);

    $this->actingAs($admin)
        ->patch(route('orders.status.update', $order), ['status' => 'cancelled'])
        ->assertRedirect(route('orders.show', $order));

    expect($order->fresh()->status)->toBe('cancelled')
        ->and($order->fresh()->stock_restored_at)->not->toBeNull()
        ->and($product->fresh()->stock)->toBe(3);
    $this->assertDatabaseCount('stock_movements', 1);

    $this->patch(route('orders.status.update', $order), ['status' => 'cancelled'])
        ->assertRedirect(route('orders.show', $order));

    expect($product->fresh()->stock)->toBe(3);
    $this->assertDatabaseCount('stock_movements', 1);
});

test('admin cannot skip shipping steps or complete an order directly', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create();
    $order = makeFulfillmentOrder($customer, ['status' => 'processing']);

    $this->actingAs($admin)
        ->patch(route('orders.status.update', $order), ['status' => 'delivered'])
        ->assertSessionHasErrors('status');

    $this->patch(route('orders.status.update', $order), ['status' => 'completed'])
        ->assertSessionHasErrors('status');

    expect($order->fresh()->status)->toBe('processing');
});

test('customer can confirm delivered order and only the owner may confirm it', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $order = makeFulfillmentOrder($owner, [
        'status' => 'delivered',
        'delivered_at' => now(),
        'auto_complete_at' => now()->addDays(3),
    ]);

    $this->actingAs($other)
        ->post(route('orders.confirm-received', $order))
        ->assertForbidden();

    $this->actingAs($owner)
        ->post(route('orders.confirm-received', $order))
        ->assertRedirect();

    expect($order->fresh()->status)->toBe('completed')
        ->and($order->fresh()->confirmed_at)->not->toBeNull()
        ->and($order->fresh()->completed_at)->not->toBeNull()
        ->and($order->fresh()->auto_complete_at)->toBeNull();
});

test('auto complete command completes due delivered orders but skips active return requests', function () {
    $owner = User::factory()->create();
    $dueOrder = makeFulfillmentOrder($owner, [
        'status' => 'delivered',
        'delivered_at' => now()->subDays(4),
        'auto_complete_at' => now()->subMinute(),
    ]);
    $heldOrder = makeFulfillmentOrder($owner, [
        'status' => 'delivered',
        'delivered_at' => now()->subDays(4),
        'auto_complete_at' => now()->subMinute(),
    ]);
    OrderReturnRequest::query()->create([
        'order_id' => $heldOrder->id,
        'user_id' => $owner->id,
        'reason' => 'damaged',
        'description' => 'Barang tiba dalam kondisi rusak.',
        'status' => 'requested',
    ]);

    $this->artisan('orders:auto-complete')->assertSuccessful();

    expect($dueOrder->fresh()->status)->toBe('completed')
        ->and($heldOrder->fresh()->status)->toBe('delivered');
});

test('customer can submit a return request with photo evidence and admin can review it', function () {
    Storage::fake('local');
    $owner = User::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $order = makeFulfillmentOrder($owner, [
        'status' => 'delivered',
        'delivered_at' => now()->subDay(),
        'auto_complete_at' => now()->addDays(2),
    ]);

    $this->actingAs($owner)
        ->post(route('orders.returns.store', $order), [
            'reason' => 'damaged',
            'description' => 'Produk mengalami kerusakan pada bagian casing.',
            'evidence' => [
                UploadedFile::fake()->image('damage.jpg'),
                UploadedFile::fake()->create('unboxing.mp4', 1024, 'video/mp4'),
            ],
        ])
        ->assertRedirect();

    $returnRequest = OrderReturnRequest::query()->firstOrFail();
    expect($returnRequest->status)->toBe('requested')
        ->and($returnRequest->evidence_paths)->toHaveCount(2)
        ->and($order->fresh()->auto_complete_at)->toBeNull();
    Storage::disk('local')->assertExists($returnRequest->evidence_paths[0]);
    Storage::disk('local')->assertExists($returnRequest->evidence_paths[1]);

    $this->actingAs($admin)
        ->get(route('order-returns.evidence', [$returnRequest, 0]))
        ->assertOk();

    $this->actingAs($admin)
        ->patch(route('order-returns.update', $returnRequest), [
            'status' => 'approved',
            'admin_note' => 'Silakan kirim barang kembali.',
            'return_recipient' => 'Gudang Toko',
            'return_phone' => '081234567890',
            'return_address' => 'Jl. Contoh No. 10, Jakarta',
            'return_instructions' => 'Cantumkan nomor pesanan.',
        ])
        ->assertRedirect();

    expect($returnRequest->fresh()->status)->toBe('approved')
        ->and($returnRequest->fresh()->return_address)->toBe('Jl. Contoh No. 10, Jakarta');

    $this->actingAs($owner)
        ->post(route('orders.returns.shipment', $returnRequest), [
            'return_courier' => 'JNE',
            'return_tracking_number' => 'RET123456',
        ])->assertRedirect();
    expect($returnRequest->fresh()->status)->toBe('return_shipped');

    $this->actingAs($admin)
        ->post(route('order-returns.receive', $returnRequest), ['inspection_note' => 'Barang diterima dan diperiksa.'])
        ->assertRedirect();
    expect($returnRequest->fresh()->status)->toBe('item_received');

    $this->actingAs($admin)
        ->post(route('order-returns.resolve', $returnRequest), [
            'resolution_type' => 'refund',
            'refund_method' => 'manual',
            'refund_amount' => 150000,
            'refund_reference' => 'TRF-REFUND-001',
            'refund_proof' => UploadedFile::fake()->image('refund-proof.jpg'),
        ])->assertRedirect();
    expect($returnRequest->fresh()->resolution_status)->toBe('processing');

    $this->actingAs($admin)
        ->post(route('order-returns.complete', $returnRequest), ['resolution_reference' => 'TRF-REFUND-001'])
        ->assertRedirect();
    expect($returnRequest->fresh()->status)->toBe('completed')
        ->and($order->fresh()->payment_status)->toBe('refunded');
});

test('admin can request additional return documents and customer can submit a video revision', function () {
    Storage::fake('local');
    $admin = User::factory()->create(['role' => 'admin']);
    $owner = User::factory()->create();
    $order = makeFulfillmentOrder($owner, [
        'status' => 'delivered',
        'delivered_at' => now()->subDay(),
        'auto_complete_at' => now()->addDays(2),
    ]);

    $this->actingAs($owner)->post(route('orders.returns.store', $order), [
        'reason' => 'damaged',
        'description' => 'Produk mengalami kerusakan pada bagian casing.',
    ])->assertRedirect();
    $returnRequest = OrderReturnRequest::query()->firstOrFail();

    $this->actingAs($admin)
        ->post(route('order-returns.revisions.request', $returnRequest), [
            'message' => 'Mohon lampirkan video unboxing yang memperlihatkan paket saat dibuka.',
            'required_documents' => ['video_unboxing'],
        ])->assertRedirect();

    $revision = OrderReturnRevision::query()->firstOrFail();
    expect($returnRequest->fresh()->status)->toBe('revision_requested')
        ->and($revision->status)->toBe('requested');

    $this->actingAs($owner)
        ->post(route('orders.returns.revisions.submit', [$returnRequest, $revision]), [
            'evidence' => [UploadedFile::fake()->image('not-a-video.jpg')],
        ])->assertSessionHasErrors('evidence');

    $this->actingAs($owner)
        ->post(route('orders.returns.revisions.submit', [$returnRequest, $revision]), [
            'customer_note' => 'Berikut video saat paket dibuka.',
            'evidence' => [UploadedFile::fake()->create('unboxing.mp4', 2048, 'video/mp4')],
        ])->assertRedirect();

    $revision->refresh();
    expect($returnRequest->fresh()->status)->toBe('requested')
        ->and($revision->status)->toBe('submitted')
        ->and($revision->evidence_paths)->toHaveCount(1)
        ->and($revision->evidence_original_names)->toBe(['unboxing.mp4']);
    Storage::disk('local')->assertExists($revision->evidence_paths[0]);

    $this->actingAs($admin)
        ->get(route('order-returns.revisions.evidence', [$returnRequest, $revision, 0]))
        ->assertOk();
});

test('admin can send replacement against the original order and inventory is recorded', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $owner = User::factory()->create();
    $category = ProductCategory::query()->create(['name' => 'Replacement test', 'slug' => 'replacement-test']);
    $product = Product::query()->create([
        'name' => 'Replacement item',
        'slug' => 'replacement-item',
        'description' => 'Replacement item description.',
        'price' => 50000,
        'stock' => 8,
        'weight_gram' => 300,
        'category_id' => $category->id,
    ]);
    $order = makeFulfillmentOrder($owner, ['status' => 'delivered', 'delivered_at' => now()->subDay()]);
    OrderItem::query()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 2,
        'price' => 50000,
        'total' => 100000,
    ]);
    $returnRequest = OrderReturnRequest::query()->create([
        'order_id' => $order->id,
        'user_id' => $owner->id,
        'reason' => 'damaged',
        'description' => 'Produk mengalami kerusakan pada casing.',
        'status' => 'item_received',
        'item_received_at' => now(),
    ]);

    $this->actingAs($admin)
        ->post(route('order-returns.resolve', $returnRequest), [
            'resolution_type' => 'replacement',
            'replacement_courier' => 'JNE',
            'replacement_tracking_number' => 'REPLACE123',
        ])->assertRedirect();

    expect($returnRequest->fresh()->resolution_status)->toBe('processing')
        ->and($returnRequest->fresh()->replacement_tracking_number)->toBe('REPLACE123')
        ->and($product->fresh()->stock)->toBe(6)
        ->and(StockMovement::query()->where('reference_type', OrderReturnRequest::class)->where('reference_id', $returnRequest->id)->count())->toBe(1)
        ->and(Order::query()->count())->toBe(1);

    $this->actingAs($admin)
        ->post(route('order-returns.complete', $returnRequest))
        ->assertRedirect();

    expect($returnRequest->fresh()->status)->toBe('completed')
        ->and($returnRequest->fresh()->replacement_delivered_at)->not->toBeNull();
});

test('return request accepts at most one unboxing video', function () {
    Storage::fake('local');
    $owner = User::factory()->create();
    $order = makeFulfillmentOrder($owner, [
        'status' => 'delivered',
        'delivered_at' => now()->subDay(),
    ]);

    $this->actingAs($owner)
        ->post(route('orders.returns.store', $order), [
            'reason' => 'damaged',
            'description' => 'Produk mengalami kerusakan pada bagian casing.',
            'evidence' => [
                UploadedFile::fake()->create('unboxing-one.mp4', 1024, 'video/mp4'),
                UploadedFile::fake()->create('unboxing-two.mp4', 1024, 'video/mp4'),
            ],
        ])
        ->assertSessionHasErrors('evidence');

    $this->assertDatabaseCount('order_return_requests', 0);
});

test('customer cannot confirm an order while a return request is active', function () {
    $owner = User::factory()->create();
    $order = makeFulfillmentOrder($owner, [
        'status' => 'delivered',
        'delivered_at' => now(),
        'auto_complete_at' => now()->addDays(3),
    ]);
    OrderReturnRequest::query()->create([
        'order_id' => $order->id,
        'user_id' => $owner->id,
        'reason' => 'damaged',
        'description' => 'Barang rusak saat diterima.',
        'status' => 'requested',
    ]);

    $this->actingAs($owner)
        ->post(route('orders.confirm-received', $order))
        ->assertSessionHasErrors('order');

    expect($order->fresh()->status)->toBe('delivered');
});

test('return request is only available to the owner within seven days of delivery', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $expired = makeFulfillmentOrder($owner, [
        'status' => 'completed',
        'delivered_at' => now()->subDays(8),
        'completed_at' => now()->subDays(5),
    ]);
    $available = makeFulfillmentOrder($owner, [
        'status' => 'delivered',
        'delivered_at' => now()->subDay(),
    ]);

    $payload = ['reason' => 'other', 'description' => 'Saya ingin mengembalikan pesanan ini.'];

    $this->actingAs($other)
        ->post(route('orders.returns.store', $available), $payload)
        ->assertForbidden();

    $this->actingAs($owner)
        ->post(route('orders.returns.store', $expired), $payload)
        ->assertSessionHasErrors('return');

    $this->assertDatabaseCount('order_return_requests', 0);
});

test('paid pending order shows that payment is verified and awaits admin processing', function () {
    $customer = User::factory()->create();
    $order = makeFulfillmentOrder($customer, [
        'status' => 'pending',
        'payment_status' => 'paid',
        'paid_at' => now(),
    ]);

    $this->actingAs($customer)
        ->get(route('orders.customer.show', $order))
        ->assertOk()
        ->assertSee('Pembayaran terverifikasi', false)
        ->assertSee('Menunggu admin memproses pesanan', false)
        ->assertDontSee('Menunggu pembayaran');
});

test('unpaid pending order continues to show that payment is awaited', function () {
    $customer = User::factory()->create();
    $order = makeFulfillmentOrder($customer, [
        'status' => 'pending',
        'payment_status' => 'pending',
    ]);

    $this->actingAs($customer)
        ->get(route('orders.customer.show', $order))
        ->assertOk()
        ->assertSee('Menunggu pembayaran');
});

test('customer tracking switches to cancellation flow while a cancellation request is pending', function () {
    $customer = User::factory()->create();
    $order = makeFulfillmentOrder($customer, ['status' => 'pending', 'payment_status' => 'paid']);
    OrderCancellationRequest::query()->create([
        'order_id' => $order->id,
        'user_id' => $customer->id,
        'reason' => 'changed_mind',
        'status' => 'requested',
    ]);

    $this->actingAs($customer)
        ->get(route('orders.customer.show', $order))
        ->assertOk()
        ->assertSee('Proses Pembatalan')
        ->assertSee('Pengajuan Dikirim')
        ->assertSee('Menunggu keputusan admin')
        ->assertDontSee('Dalam Perjalanan');
});

test('customer tracking shows refund completion for a completed paid cancellation', function () {
    $customer = User::factory()->create();
    $order = makeFulfillmentOrder($customer, [
        'status' => 'cancelled',
        'payment_status' => 'refunded',
    ]);
    OrderCancellationRequest::query()->create([
        'order_id' => $order->id,
        'user_id' => $customer->id,
        'reason' => 'duplicate',
        'status' => 'completed',
        'reviewed_at' => now()->subHour(),
        'resolved_at' => now(),
    ]);

    $this->actingAs($customer)
        ->get(route('orders.customer.show', $order))
        ->assertOk()
        ->assertSee('Proses Pembatalan')
        ->assertSee('Refund Diproses')
        ->assertSee('Pembatalan selesai')
        ->assertSee('Refund tercatat');
});

test('customer tracking shows return request progress separately from order fulfillment', function () {
    $customer = User::factory()->create();
    $order = makeFulfillmentOrder($customer, [
        'status' => 'delivered',
        'delivered_at' => now()->subDay(),
    ]);
    OrderReturnRequest::query()->create([
        'order_id' => $order->id,
        'user_id' => $customer->id,
        'reason' => 'damaged',
        'description' => 'Barang rusak saat diterima.',
        'status' => 'approved',
        'reviewed_at' => now(),
    ]);

    $this->actingAs($customer)
        ->get(route('orders.customer.show', $order))
        ->assertOk()
        ->assertSee('Proses Pengembalian')
        ->assertSee('Disetujui Admin')
        ->assertSee('Pengembalian disetujui')
        ->assertDontSee('Lacak Pesanan');
});

test('customer order detail only exposes its private realtime subscription when enabled', function () {
    $customer = User::factory()->create();
    $order = makeFulfillmentOrder($customer);

    $this->actingAs($customer)
        ->get(route('orders.customer.show', $order))
        ->assertOk()
        ->assertDontSee('data-order-realtime', false);

    config(['realtime.enabled' => true, 'realtime.order_status' => true]);

    $this->get(route('orders.customer.show', $order))
        ->assertOk()
        ->assertSee('data-order-realtime="'.$order->id.'"', false);
});

test('admin status changes broadcast to the order private channel after the update', function () {
    config(['realtime.enabled' => true, 'realtime.order_status' => true]);
    Event::fake([OrderStatusUpdated::class]);

    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create();
    $order = makeFulfillmentOrder($customer, ['status' => 'pending']);

    $this->actingAs($admin)
        ->patch(route('orders.status.update', $order), ['status' => 'processing'])
        ->assertRedirect(route('orders.show', $order));

    Event::assertDispatched(OrderStatusUpdated::class, function (OrderStatusUpdated $event) use ($order): bool {
        return $event->orderId === (string) $order->id
            && $event->orderData['status'] === 'processing'
            && $event->orderData['order_number'] === $order->order_number;
    });

    expect($customer->notifications()->where('type', OrderAlert::class)->count())->toBe(1);
});

test('only the order owner and administrators may authorize its private channel', function () {
    $owner = User::factory()->create();
    $otherCustomer = User::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $order = makeFulfillmentOrder($owner);

    $this->actingAs($owner)->postJson('/broadcasting/auth', [
        'socket_id' => '1234.5678',
        'channel_name' => 'private-orders.'.$order->id,
    ])->assertOk();

    $this->actingAs($otherCustomer)->postJson('/broadcasting/auth', [
        'socket_id' => '1234.5678',
        'channel_name' => 'private-orders.'.$order->id,
    ])->assertForbidden();

    $this->actingAs($admin)->postJson('/broadcasting/auth', [
        'socket_id' => '1234.5678',
        'channel_name' => 'private-orders.'.$order->id,
    ])->assertOk();
});

test('order processing shipping and delivery transitions queue transactional emails', function () {
    Mail::fake();
    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create();
    $order = makeFulfillmentOrder($customer, ['status' => 'pending']);

    $this->actingAs($admin)->patch(route('orders.status.update', $order), ['status' => 'processing'])->assertRedirect();
    $this->patch(route('orders.status.update', $order), [
        'status' => 'shipped',
        'tracking_number' => 'JNE-EMAIL-123',
    ])->assertRedirect();
    $this->patch(route('orders.status.update', $order), ['status' => 'delivered'])->assertRedirect();

    Mail::assertQueued(OrderTransactionalMail::class, 3);
    Mail::assertQueued(OrderTransactionalMail::class, fn (OrderTransactionalMail $mail): bool => $mail->type === 'order_processing');
    Mail::assertQueued(OrderTransactionalMail::class, fn (OrderTransactionalMail $mail): bool => $mail->type === 'order_shipped' && $mail->order->tracking_number === 'JNE-EMAIL-123');
    Mail::assertQueued(OrderTransactionalMail::class, fn (OrderTransactionalMail $mail): bool => $mail->type === 'order_delivered');
});

test('refund resolution rejects an amount above the remaining refundable balance', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $owner = User::factory()->create();
    $order = makeFulfillmentOrder($owner, [
        'total' => 105000,
        'payment_payload' => ['transaction_status' => 'settlement', 'refund_amount' => '100000.00'],
    ]);
    $returnRequest = OrderReturnRequest::query()->create([
        'order_id' => $order->id,
        'user_id' => $owner->id,
        'reason' => 'damaged',
        'description' => 'Produk mengalami kerusakan pada bagian casing.',
        'status' => 'item_received',
        'item_received_at' => now(),
    ]);

    $this->actingAs($admin)
        ->post(route('order-returns.resolve', $returnRequest), [
            'resolution_type' => 'refund',
            'refund_method' => 'midtrans',
            'refund_amount' => 6000,
        ])
        ->assertSessionHasErrors('refund_amount');

    expect($returnRequest->fresh()->resolution_status)->toBe('pending')
        ->and($order->fresh()->payment_status)->toBe('paid');
});

test('Midtrans refund completion requires matching remote transaction and refund amount', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $owner = User::factory()->create();
    $order = makeFulfillmentOrder($owner, [
        'total' => 105000,
        'payment_order_id' => 'PAY-ORDER-VERIFY',
        'transaction_id' => 'midtrans-verify-transaction',
        'payment_payload' => ['transaction_status' => 'settlement'],
    ]);
    $returnRequest = OrderReturnRequest::query()->create([
        'order_id' => $order->id,
        'user_id' => $owner->id,
        'reason' => 'damaged',
        'description' => 'Produk mengalami kerusakan pada bagian casing.',
        'status' => 'item_received',
        'item_received_at' => now(),
        'resolution_type' => 'refund',
        'resolution_status' => 'processing',
        'refund_amount' => 50000,
        'refund_method' => 'midtrans',
        'refund_reference' => 'RET-VERIFY',
    ]);

    $payments = Mockery::mock(MidtransPaymentService::class);
    $payments->shouldReceive('fetchStatus')->once()->with(Mockery::on(fn ($candidate) => $candidate->is($order)))
        ->andReturn((object) [
            'order_id' => 'PAY-ORDER-VERIFY',
            'transaction_id' => 'midtrans-verify-transaction',
            'transaction_status' => 'partial_refund',
            'refund_amount' => '49999.00',
        ]);
    $this->app->instance(MidtransPaymentService::class, $payments);

    $this->actingAs($admin)
        ->post(route('order-returns.complete', $returnRequest))
        ->assertSessionHasErrors('resolution');

    expect($returnRequest->fresh()->resolution_status)->toBe('processing')
        ->and($order->fresh()->payment_status)->toBe('paid');
});
