<?php

use App\Models\Order;
use App\Models\OrderCancellationRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function cancellationTestOrder(User $user, array $attributes = []): Order
{
    return Order::query()->create(array_merge([
        'user_id' => $user->id,
        'cart_id' => null,
        'user_name' => $user->name,
        'user_email' => $user->email,
        'total' => 150000,
        'status' => 'pending',
        'payment_status' => 'pending',
    ], $attributes));
}

function cancellationTestProduct(int $stock = 5): Product
{
    $category = ProductCategory::query()->create([
        'name' => 'Cancellation Test Category',
        'slug' => 'cancellation-test-'.uniqid(),
    ]);

    return Product::query()->create([
        'name' => 'Cancellation Test Product',
        'slug' => 'cancellation-test-product-'.uniqid(),
        'description' => 'Product used for cancellation tests.',
        'image' => 'products/test.jpg',
        'price' => 150000,
        'stock' => $stock,
        'category_id' => $category->id,
    ]);
}

test('customer can directly cancel an unpaid pending order and stock is restored once', function () {
    $customer = User::factory()->create();
    $product = cancellationTestProduct(stock: 3);
    $order = cancellationTestOrder($customer);
    $order->orderItems()->create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 2,
        'price' => 150000,
        'total' => 300000,
    ]);

    $this->actingAs($customer)
        ->post(route('orders.cancel', $order), [
            'reason' => 'changed_mind',
            'description' => 'Saya berubah pikiran.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Pesanan berhasil dibatalkan.');

    expect($order->fresh()->status)->toBe('cancelled')
        ->and($order->fresh()->stock_restored_at)->not->toBeNull()
        ->and($order->fresh()->cancellationRequest)->toBeNull();
    $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5]);
    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'type' => 'in',
        'quantity' => 2,
        'stock_before' => 3,
        'stock_after' => 5,
        'reference_type' => Order::class,
        'reference_id' => $order->id,
    ]);
});

test('customer paid order cancellation requires admin approval and holds shipment', function () {
    $customer = User::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $order = cancellationTestOrder($customer, ['status' => 'processing', 'payment_status' => 'paid']);

    $this->actingAs($customer)
        ->post(route('orders.cancel', $order), ['reason' => 'wrong_order'])
        ->assertRedirect()
        ->assertSessionHas('success');

    $cancellation = OrderCancellationRequest::query()->firstOrFail();
    expect($cancellation->status)->toBe('requested')
        ->and($order->fresh()->status)->toBe('processing');

    $this->actingAs($admin)
        ->patch(route('orders.status.update', $order), [
            'status' => 'shipped',
            'tracking_number' => 'JNE-123',
        ])
        ->assertSessionHasErrors('status');

    $this->patch(route('order-cancellations.update', $cancellation), [
        'status' => 'rejected',
        'admin_note' => 'Pesanan sudah mulai dikemas dan tidak dapat dibatalkan.',
    ])->assertRedirect();

    expect($cancellation->fresh()->status)->toBe('rejected');

    $this->patch(route('orders.status.update', $order), [
        'status' => 'shipped',
        'tracking_number' => 'JNE-123',
    ])->assertRedirect();
    expect($order->fresh()->status)->toBe('shipped');
});

test('admin cannot start processing while a cancellation request is awaiting a decision', function () {
    $customer = User::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $order = cancellationTestOrder($customer, ['payment_status' => 'paid']);
    OrderCancellationRequest::query()->create([
        'order_id' => $order->id,
        'user_id' => $customer->id,
        'reason' => 'changed_mind',
        'status' => 'requested',
    ]);

    $this->actingAs($admin)
        ->patch(route('orders.status.update', $order), ['status' => 'processing'])
        ->assertSessionHasErrors('status');

    expect($order->fresh()->status)->toBe('pending');
});

test('approved paid cancellation becomes cancelled and restores stock only after refund is recorded', function () {
    $customer = User::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $product = cancellationTestProduct(stock: 2);
    $order = cancellationTestOrder($customer, ['status' => 'processing', 'payment_status' => 'paid']);
    $order->orderItems()->create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 1,
        'price' => 150000,
        'total' => 150000,
    ]);
    $cancellation = OrderCancellationRequest::query()->create([
        'order_id' => $order->id,
        'user_id' => $customer->id,
        'reason' => 'duplicate',
        'status' => 'requested',
    ]);

    $this->actingAs($admin)
        ->patch(route('order-cancellations.update', $cancellation), [
            'status' => 'approved',
            'admin_note' => 'Disetujui. Refund sedang diproses.',
        ])->assertRedirect();

    expect($order->fresh()->status)->toBe('processing')
        ->and($order->fresh()->payment_status)->toBe('paid')
        ->and($product->fresh()->stock)->toBe(2);

    $this->patch(route('order-cancellations.update', $cancellation), [
        'status' => 'completed',
    ])->assertSessionHasErrors('admin_note');

    Storage::fake('local');
    $this->patch(route('order-cancellations.update', $cancellation), [
        'status' => 'completed',
        'admin_note' => 'Refund Midtrans manual ref: REF-12345',
        'refund_proof' => UploadedFile::fake()->image('refund-proof.png'),
    ])->assertRedirect();

    expect($order->fresh()->status)->toBe('cancelled')
        ->and($order->fresh()->payment_status)->toBe('refunded')
        ->and($cancellation->fresh()->status)->toBe('completed')
        ->and($cancellation->fresh()->refund_proof_original_name)->toBe('refund-proof.png')
        ->and($order->fresh()->stock_restored_at)->not->toBeNull();
    Storage::disk('local')->assertExists($cancellation->fresh()->refund_proof_path);
    $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 3]);
});

test('customer cannot cancel shipped order or another customers order', function () {
    $customer = User::factory()->create();
    $other = User::factory()->create();
    $shipped = cancellationTestOrder($customer, ['status' => 'shipped', 'payment_status' => 'paid']);
    $pending = cancellationTestOrder($customer);

    $this->actingAs($customer)
        ->post(route('orders.cancel', $shipped), ['reason' => 'other'])
        ->assertSessionHasErrors('cancellation');

    $this->actingAs($other)
        ->post(route('orders.cancel', $pending), ['reason' => 'other'])
        ->assertForbidden();
});

test('late Midtrans settlement after unpaid cancellation is flagged for admin refund', function () {
    $customer = User::factory()->create();
    $order = cancellationTestOrder($customer, [
        'status' => 'cancelled',
        'payment_status' => 'pending',
        'payment_order_id' => 'CANCELLED-'.uniqid(),
    ]);
    $serverKey = 'cancel-test-server-key';
    config(['midtrans.server_key' => $serverKey, 'midtrans.merchant_id' => null]);
    $orderId = $order->payment_order_id;
    $statusCode = '200';
    $grossAmount = '150000.00';
    $signature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

    $this->postJson(route('midtrans.callback'), [
        'order_id' => $orderId,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'signature_key' => $signature,
        'transaction_status' => 'settlement',
        'fraud_status' => 'accept',
    ])->assertOk()->assertJson(['status' => 'processed']);

    expect($order->fresh()->status)->toBe('cancelled')
        ->and($order->fresh()->payment_status)->toBe('paid')
        ->and($order->fresh()->cancellationRequest->status)->toBe('approved');
});

test('paid cancellation cannot be completed without a refund proof and customer can view uploaded proof', function () {
    Storage::fake('local');
    $customer = User::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $order = cancellationTestOrder($customer, ['status' => 'processing', 'payment_status' => 'paid']);
    $cancellation = OrderCancellationRequest::query()->create([
        'order_id' => $order->id,
        'user_id' => $customer->id,
        'reason' => 'changed_mind',
        'status' => 'approved',
    ]);

    $this->actingAs($admin)
        ->patch(route('order-cancellations.update', $cancellation), [
            'status' => 'completed',
            'admin_note' => 'Refund reference REF-987',
        ])
        ->assertSessionHasErrors('refund_proof');

    expect($order->fresh()->payment_status)->toBe('paid')
        ->and($cancellation->fresh()->status)->toBe('approved');

    $this->actingAs($admin)
        ->patch(route('order-cancellations.update', $cancellation), [
            'status' => 'completed',
            'admin_note' => 'Refund reference REF-987',
            'refund_proof' => UploadedFile::fake()->image('refund.png'),
        ])
        ->assertRedirect();

    $cancellation->refresh();
    expect($cancellation->refund_proof_path)->not->toBeNull();

    $this->actingAs($customer)
        ->get(route('order-cancellations.proof', $cancellation))
        ->assertOk();

    $otherCustomer = User::factory()->create();
    $this->actingAs($otherCustomer)
        ->get(route('order-cancellations.proof', $cancellation))
        ->assertForbidden();
});

test('admin can view cancellation refund proof', function () {
    Storage::fake('local');
    $customer = User::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $order = cancellationTestOrder($customer, ['payment_status' => 'refunded', 'status' => 'cancelled']);
    $path = UploadedFile::fake()->image('proof.png')->store('order-cancellations/1/refund', 'local');
    $cancellation = OrderCancellationRequest::query()->create([
        'order_id' => $order->id,
        'user_id' => $customer->id,
        'reason' => 'other',
        'status' => 'completed',
        'refund_proof_path' => $path,
        'refund_proof_original_name' => 'proof.png',
    ]);

    $this->actingAs($admin)
        ->get(route('order-cancellations.proof', $cancellation))
        ->assertOk();
});

test('admin order page renders reusable refund proof uploader with image preview support', function () {
    $customer = User::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $order = cancellationTestOrder($customer, ['status' => 'processing', 'payment_status' => 'paid']);
    OrderCancellationRequest::query()->create([
        'order_id' => $order->id,
        'user_id' => $customer->id,
        'reason' => 'changed_mind',
        'status' => 'approved',
    ]);

    $this->actingAs($admin)
        ->get(route('orders.show', $order))
        ->assertOk()
        ->assertSee('data-upload-mode="deferred"', false)
        ->assertSee('name="refund_proof"', false)
        ->assertSee('image/jpeg,image/png,image/webp,application/pdf', false)
        ->assertSee('data-file-upload-template', false);
});
