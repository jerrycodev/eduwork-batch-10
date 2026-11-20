<?php

use App\Mail\OrderTransactionalMail;
use App\Models\CustomerActivityLog;
use App\Models\Order;
use App\Models\PaymentWebhookLog;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Notifications\OrderAlert;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

test('verified customer opening the legacy checkout URL is redirected to the cart', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer, 'customer')->get(route('checkout'))
        ->assertRedirect(route('cart'))
        ->assertSessionHas('info');
});

test('midtrans callback rejects an invalid signature', function () {
    config(['midtrans.server_key' => 'test-server-key']);

    $this->postJson(route('midtrans.callback'), [
        'order_id' => 'ORDER-123',
        'status_code' => '200',
        'gross_amount' => '150000.00',
        'signature_key' => 'invalid-signature',
    ])->assertOk()
        ->assertJson(['status' => 'ignored', 'reason' => 'invalid_signature']);

    $log = PaymentWebhookLog::query()->sole();
    expect($log->result)->toBe('rejected')
        ->and($log->signature_valid)->toBeFalse()
        ->and($log->http_status)->toBe(200)
        ->and($log->message)->toContain('Signature Midtrans tidak valid');
});

test('midtrans callback acknowledges a correctly signed notification', function () {
    $serverKey = 'test-server-key';
    config(['midtrans.server_key' => $serverKey, 'midtrans.merchant_id' => null]);

    $orderId = 'ORDER-123';
    $statusCode = '200';
    $grossAmount = '150000.00';
    $signature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

    $this->postJson(route('midtrans.callback'), [
        'order_id' => $orderId,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'signature_key' => $signature,
    ])->assertOk()
        ->assertJson(['status' => 'ignored', 'reason' => 'order_not_found']);

    $log = PaymentWebhookLog::query()->sole();
    expect($log->result)->toBe('rejected')
        ->and($log->signature_valid)->toBeTrue()
        ->and($log->payment_order_id)->toBe($orderId)
        ->and($log->message)->toContain('tidak ditemukan');
});

test('midtrans callback endpoint is exempt from browser CSRF validation', function () {
    $this->postJson(route('midtrans.callback'), [])
        ->assertOk()
        ->assertJson(['status' => 'ignored', 'reason' => 'incomplete_payload']);
});

test('verified Midtrans settlement updates payment status but leaves fulfillment status for admin', function () {
    Mail::fake();
    $user = User::factory()->create();
    User::factory()->create(['role' => 'admin']);
    $order = Order::query()->create([
        'user_id' => $user->id,
        'cart_id' => null,
        'user_name' => $user->name,
        'user_email' => $user->email,
        'total' => 105000,
        'status' => 'pending',
        'payment_status' => 'pending',
        'payment_order_id' => 'ORDER-'.uniqid(),
    ]);
    $serverKey = 'test-server-key';
    config(['midtrans.server_key' => $serverKey, 'midtrans.merchant_id' => 'TEST-MERCHANT']);
    $orderId = $order->payment_order_id;
    $statusCode = '200';
    $grossAmount = '105000.00';
    $signature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

    $this->postJson(route('midtrans.callback'), [
        'order_id' => $orderId,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'signature_key' => $signature,
        'transaction_status' => 'settlement',
        'transaction_id' => 'midtrans-transaction-123',
        'payment_type' => 'gopay',
        'fraud_status' => 'accept',
        'merchant_id' => 'TEST-MERCHANT',
    ])->assertOk()->assertJson(['status' => 'processed']);

    $order->refresh();
    $webhookLog = PaymentWebhookLog::query()->sole();
    $activityLog = CustomerActivityLog::query()->where('action', 'payment.paid')->sole();
    expect($activityLog->user_id)->toBe($user->id)
        ->and($activityLog->subject_id)->toBe($order->id)
        ->and($activityLog->metadata['order_number'])->toBe($order->order_number)
        ->and($activityLog->metadata['payment_type'])->toBe('gopay');
    expect($webhookLog->result)->toBe('processed')
        ->and($webhookLog->signature_valid)->toBeTrue()
        ->and($webhookLog->order_id)->toBe($order->id)
        ->and($webhookLog->transaction_status)->toBe('settlement')
        ->and($webhookLog->mapped_status)->toBe('paid')
        ->and($webhookLog->payload)->not->toHaveKey('signature_key');

    expect($order->payment_status)->toBe('paid')
        ->and($order->status)->toBe('pending')
        ->and($order->transaction_id)->toBe('midtrans-transaction-123')
        ->and($order->payment_type)->toBe('gopay')
        ->and($order->paid_at)->not->toBeNull();

    expect($user->notifications()->where('type', OrderAlert::class)->count())->toBe(1)
        ->and(User::query()->where('role', 'admin')->first()->notifications()->count())->toBe(1);

    Mail::assertQueued(OrderTransactionalMail::class, fn (OrderTransactionalMail $mail): bool => $mail->order->is($order) && $mail->type === 'payment_successful'
    );

    // Midtrans may retry the same webhook; it must not create duplicate notifications.
    $this->postJson(route('midtrans.callback'), [
        'order_id' => $orderId, 'status_code' => $statusCode, 'gross_amount' => $grossAmount,
        'signature_key' => $signature, 'transaction_status' => 'settlement',
        'transaction_id' => 'midtrans-transaction-123', 'payment_type' => 'gopay',
        'fraud_status' => 'accept', 'merchant_id' => 'TEST-MERCHANT',
    ])->assertOk();

    expect($user->notifications()->count())->toBe(1)
        ->and(User::query()->where('role', 'admin')->first()->notifications()->count())->toBe(1)
        ->and(CustomerActivityLog::query()->where('action', 'payment.paid')->count())->toBe(1);

    Mail::assertQueued(OrderTransactionalMail::class, 1);
});

test('Midtrans callback does not update payment when gross amount does not match the order', function () {
    $user = User::factory()->create();
    $order = Order::query()->create([
        'user_id' => $user->id,
        'cart_id' => null,
        'user_name' => $user->name,
        'user_email' => $user->email,
        'total' => 105000,
        'status' => 'pending',
        'payment_status' => 'pending',
        'payment_order_id' => 'ORDER-'.uniqid(),
    ]);
    $serverKey = 'test-server-key';
    config(['midtrans.server_key' => $serverKey, 'midtrans.merchant_id' => null]);
    $orderId = $order->payment_order_id;
    $statusCode = '200';
    $grossAmount = '100000.00';
    $signature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

    $this->postJson(route('midtrans.callback'), [
        'order_id' => $orderId,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'signature_key' => $signature,
        'transaction_status' => 'settlement',
    ])->assertOk()->assertJson(['status' => 'ignored', 'reason' => 'amount_mismatch']);

    expect($order->fresh()->payment_status)->toBe('pending')
        ->and(PaymentWebhookLog::query()->sole()->result)->toBe('rejected')
        ->and(PaymentWebhookLog::query()->sole()->message)->toContain('Nominal Midtrans tidak cocok');
});

test('admin can set order to processing only after payment is verified', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create();
    $order = Order::query()->create([
        'user_id' => $customer->id,
        'cart_id' => null,
        'user_name' => $customer->name,
        'user_email' => $customer->email,
        'total' => 105000,
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);

    $this->actingAs($admin, 'admin')
        ->patch(route('orders.status.update', $order), ['status' => 'processing'])
        ->assertSessionHasErrors('status');
    expect($order->fresh()->status)->toBe('pending');

    $order->update(['payment_status' => 'paid']);
    $this->patch(route('orders.status.update', $order), ['status' => 'processing'])
        ->assertRedirect(route('orders.show', $order));
    expect($order->fresh()->status)->toBe('processing');
});

test('notification routes only expose and mutate the authenticated user notifications', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $notification = $owner->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => OrderAlert::class,
        'data' => ['title' => 'Pembayaran berhasil', 'message' => 'Tes', 'url' => route('notifications.index')],
    ]);

    $this->actingAs($other, 'customer')->post(route('notifications.read', $notification->id))->assertNotFound();
    expect($notification->fresh()->read_at)->toBeNull();

    $this->actingAs($owner, 'customer')->get(route('notifications.index'))->assertOk()->assertSee('Pembayaran berhasil');
    $this->actingAs($owner, 'customer')->post(route('notifications.read', $notification->id))->assertRedirect(route('notifications.index'));
    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('Midtrans partial refund callback preserves the partial refund state', function () {
    $user = User::factory()->create();
    $order = Order::query()->create([
        'user_id' => $user->id,
        'cart_id' => null,
        'user_name' => $user->name,
        'user_email' => $user->email,
        'total' => 105000,
        'status' => 'completed',
        'payment_status' => 'paid',
        'payment_order_id' => 'ORDER-'.uniqid(),
    ]);
    $serverKey = 'test-server-key';
    config(['midtrans.server_key' => $serverKey, 'midtrans.merchant_id' => null]);
    $orderId = $order->payment_order_id;
    $statusCode = '200';
    $grossAmount = '105000.00';

    $this->postJson(route('midtrans.callback'), [
        'order_id' => $orderId,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'signature_key' => hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey),
        'transaction_status' => 'partial_refund',
    ])->assertOk()->assertJson(['status' => 'processed']);

    expect($order->fresh()->payment_status)->toBe('partially_refunded');
});

test('late pending callback cannot reopen an expired payment and malformed amount is rejected', function () {
    $user = User::factory()->create();
    $order = Order::query()->create([
        'user_id' => $user->id,
        'cart_id' => null,
        'user_name' => $user->name,
        'user_email' => $user->email,
        'total' => 105000,
        'status' => 'pending',
        'payment_status' => 'expired',
        'payment_order_id' => 'ORDER-'.uniqid(),
    ]);
    $serverKey = 'test-server-key';
    config(['midtrans.server_key' => $serverKey, 'midtrans.merchant_id' => null]);
    $orderId = $order->payment_order_id;
    $statusCode = '201';
    $grossAmount = '105000.00';

    $this->postJson(route('midtrans.callback'), [
        'order_id' => $orderId,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'signature_key' => hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey),
        'transaction_status' => 'pending',
    ])->assertOk()->assertJson(['status' => 'processed']);

    expect($order->fresh()->payment_status)->toBe('expired');

    $malformedAmount = '1.05e5';
    $this->postJson(route('midtrans.callback'), [
        'order_id' => $orderId,
        'status_code' => $statusCode,
        'gross_amount' => $malformedAmount,
        'signature_key' => hash('sha512', $orderId.$statusCode.$malformedAmount.$serverKey),
        'transaction_status' => 'settlement',
    ])->assertOk()->assertJson(['status' => 'ignored', 'reason' => 'amount_mismatch']);

    expect($order->fresh()->payment_status)->toBe('expired');
});

test('verified late settlement after expiry is recorded once without advancing fulfillment', function () {
    Mail::fake();
    $user = User::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $order = Order::query()->create([
        'user_id' => $user->id,
        'cart_id' => null,
        'user_name' => $user->name,
        'user_email' => $user->email,
        'total' => 105000,
        'status' => 'pending',
        'payment_status' => 'expired',
        'payment_order_id' => 'ORDER-'.uniqid(),
    ]);

    $serverKey = 'test-server-key';
    config(['midtrans.server_key' => $serverKey, 'midtrans.merchant_id' => null]);
    $orderId = $order->payment_order_id;
    $statusCode = '200';
    $grossAmount = '105000.00';
    $callback = [
        'order_id' => $orderId,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'signature_key' => hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey),
        'transaction_status' => 'settlement',
        'transaction_id' => 'late-settlement-transaction',
        'payment_type' => 'qris',
        'fraud_status' => 'accept',
    ];

    $this->postJson(route('midtrans.callback'), $callback)
        ->assertOk()
        ->assertJson(['status' => 'processed']);

    expect($order->fresh()->payment_status)->toBe('paid')
        ->and($order->fresh()->status)->toBe('pending')
        ->and($order->fresh()->paid_at)->not->toBeNull()
        ->and($user->notifications()->where('type', OrderAlert::class)->count())->toBe(1)
        ->and($admin->notifications()->where('type', OrderAlert::class)->count())->toBe(1);

    Mail::assertQueued(OrderTransactionalMail::class, 1);

    // A replay of the late settlement must not duplicate side effects.
    $this->postJson(route('midtrans.callback'), $callback)
        ->assertOk()
        ->assertJson(['status' => 'processed']);

    expect($user->notifications()->where('type', OrderAlert::class)->count())->toBe(1)
        ->and($admin->notifications()->where('type', OrderAlert::class)->count())->toBe(1);

    Mail::assertQueued(OrderTransactionalMail::class, 1);
});

test('repeated partial refund callback keeps the highest cumulative refund without changing fulfillment', function () {
    $user = User::factory()->create();
    $order = Order::query()->create([
        'user_id' => $user->id,
        'cart_id' => null,
        'user_name' => $user->name,
        'user_email' => $user->email,
        'total' => 105000,
        'status' => 'completed',
        'payment_status' => 'partially_refunded',
        'payment_order_id' => 'ORDER-'.uniqid(),
        'payment_payload' => ['transaction_status' => 'partial_refund', 'refund_amount' => '25000.00'],
    ]);
    $serverKey = 'test-server-key';
    config(['midtrans.server_key' => $serverKey, 'midtrans.merchant_id' => null]);
    $orderId = $order->payment_order_id;
    $statusCode = '200';
    $grossAmount = '105000.00';

    $this->postJson(route('midtrans.callback'), [
        'order_id' => $orderId,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'signature_key' => hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey),
        'transaction_status' => 'partial_refund',
        'refund_amount' => '40000.00',
    ])->assertOk()->assertJson(['status' => 'processed']);

    expect($order->fresh()->payment_status)->toBe('partially_refunded')
        ->and(data_get($order->fresh()->payment_payload, 'refund_amount'))->toBe('40000.00')
        ->and($order->fresh()->status)->toBe('completed');

    $this->postJson(route('midtrans.callback'), [
        'order_id' => $orderId,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'signature_key' => hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey),
        'transaction_status' => 'partial_refund',
        'refund_amount' => '20000.00',
    ])->assertOk();

    expect(data_get($order->fresh()->payment_payload, 'refund_amount'))->toBe('40000.00');
});

test('Midtrans expiry automatically cancels the unpaid order restores stock and notifies customer and admins once', function () {
    $customer = User::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $category = ProductCategory::query()->create([
        'name' => 'Payment Expiry Category',
        'slug' => 'payment-expiry-'.uniqid(),
    ]);
    $product = Product::query()->create([
        'name' => 'Payment Expiry Product',
        'slug' => 'payment-expiry-product-'.uniqid(),
        'description' => 'Product used for payment expiry tests.',
        'image' => 'products/test.jpg',
        'price' => 105000,
        'stock' => 3,
        'category_id' => $category->id,
    ]);
    $order = Order::query()->create([
        'user_id' => $customer->id,
        'cart_id' => null,
        'user_name' => $customer->name,
        'user_email' => $customer->email,
        'total' => 105000,
        'status' => 'pending',
        'payment_status' => 'pending',
        'payment_order_id' => 'ORDER-'.uniqid(),
    ]);
    $order->orderItems()->create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 2,
        'price' => 52500,
        'total' => 105000,
    ]);

    $serverKey = 'test-server-key';
    config(['midtrans.server_key' => $serverKey, 'midtrans.merchant_id' => null]);
    $orderId = $order->payment_order_id;
    $statusCode = '200';
    $grossAmount = '105000.00';
    $callback = [
        'order_id' => $orderId,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'signature_key' => hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey),
        'transaction_status' => 'expire',
    ];

    $this->postJson(route('midtrans.callback'), $callback)
        ->assertOk()
        ->assertJson(['status' => 'processed']);

    expect($order->fresh()->payment_status)->toBe('expired')
        ->and($order->fresh()->status)->toBe('cancelled')
        ->and($order->fresh()->stock_restored_at)->not->toBeNull()
        ->and($order->fresh()->cancellationRequest->status)->toBe('completed')
        ->and($customer->notifications()->where('type', OrderAlert::class)->count())->toBe(1)
        ->and($admin->notifications()->where('type', OrderAlert::class)->count())->toBe(1);

    $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5]);
    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'type' => 'in',
        'quantity' => 2,
        'reference_type' => Order::class,
        'reference_id' => $order->id,
    ]);

    $this->postJson(route('midtrans.callback'), $callback)
        ->assertOk()
        ->assertJson(['status' => 'processed']);

    expect($customer->notifications()->where('type', OrderAlert::class)->count())->toBe(1)
        ->and($admin->notifications()->where('type', OrderAlert::class)->count())->toBe(1);
    $this->assertDatabaseCount('stock_movements', 1);
});

test('late Midtrans settlement after automatic expiry keeps order cancelled and flags manual refund for admins', function () {
    Mail::fake();
    $customer = User::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $order = Order::query()->create([
        'user_id' => $customer->id,
        'cart_id' => null,
        'user_name' => $customer->name,
        'user_email' => $customer->email,
        'total' => 105000,
        'status' => 'pending',
        'payment_status' => 'pending',
        'payment_order_id' => 'ORDER-'.uniqid(),
    ]);

    $serverKey = 'test-server-key';
    config(['midtrans.server_key' => $serverKey, 'midtrans.merchant_id' => null]);
    $orderId = $order->payment_order_id;
    $grossAmount = '105000.00';
    $signature = hash('sha512', $orderId.'200'.$grossAmount.$serverKey);

    $this->postJson(route('midtrans.callback'), [
        'order_id' => $orderId,
        'status_code' => '200',
        'gross_amount' => $grossAmount,
        'signature_key' => $signature,
        'transaction_status' => 'expire',
    ])->assertOk();

    expect($order->fresh()->status)->toBe('cancelled')
        ->and($order->fresh()->payment_status)->toBe('expired')
        ->and($order->fresh()->cancellationRequest->status)->toBe('completed');

    $this->postJson(route('midtrans.callback'), [
        'order_id' => $orderId,
        'status_code' => '200',
        'gross_amount' => $grossAmount,
        'signature_key' => $signature,
        'transaction_status' => 'settlement',
        'transaction_id' => 'late-paid-transaction',
        'payment_type' => 'qris',
        'fraud_status' => 'accept',
    ])->assertOk();

    expect($order->fresh()->status)->toBe('cancelled')
        ->and($order->fresh()->payment_status)->toBe('paid')
        ->and($order->fresh()->cancellationRequest->status)->toBe('approved')
        ->and($order->fresh()->cancellationRequest->admin_note)->toContain('Refund perlu diproses secara manual');

    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $customer->id,
        'type' => OrderAlert::class,
    ]);
    expect($customer->notifications()->count())->toBe(2)
        ->and($admin->notifications()->count())->toBe(2);

    Mail::assertQueued(OrderTransactionalMail::class, 1);
});

test('Midtrans callback records incomplete payloads as rejected without exposing signature fields', function () {
    $this->postJson(route('midtrans.callback'), [
        'order_id' => 'ORDER-INCOMPLETE', 'signature_key' => 'must-not-be-stored',
        'card_number' => '4111111111111111',
    ])->assertOk()->assertJson(['status' => 'ignored', 'reason' => 'incomplete_payload']);

    $log = PaymentWebhookLog::query()->sole();
    expect($log->result)->toBe('rejected')
        ->and($log->signature_valid)->toBeNull()
        ->and($log->payload)->not->toHaveKey('signature_key')
        ->and($log->payload)->not->toHaveKey('card_number');
});

test('Payment Reconciliation page displays the Midtrans webhook audit log', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    PaymentWebhookLog::query()->create([
        'event' => 'transaction.settlement',
        'payment_order_id' => 'ORDER-AUDIT-VIEW',
        'transaction_status' => 'settlement',
        'mapped_status' => 'paid',
        'signature_valid' => true,
        'result' => 'processed',
        'http_status' => 200,
        'message' => 'Notifikasi Midtrans berhasil diproses.',
        'payload' => ['order_id' => 'ORDER-AUDIT-VIEW', 'transaction_status' => 'settlement'],
        'received_at' => now(),
        'processed_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('payment-reconciliation.index'))
        ->assertOk()
        ->assertSee('Webhook masuk dari Midtrans')
        ->assertSee('transaction.settlement')
        ->assertSee('ORDER-AUDIT-VIEW')
        ->assertSee('Signature key dan data kartu tidak disimpan.');
});
