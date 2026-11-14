<?php

use App\Models\Order;
use App\Models\OrderCancellationRequest;
use App\Models\OrderReturnRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
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
            'evidence' => [UploadedFile::fake()->image('damage.jpg')],
        ])
        ->assertRedirect();

    $returnRequest = OrderReturnRequest::query()->firstOrFail();
    expect($returnRequest->status)->toBe('requested')
        ->and($returnRequest->evidence_paths)->toHaveCount(1)
        ->and($order->fresh()->auto_complete_at)->toBeNull();
    Storage::disk('local')->assertExists($returnRequest->evidence_paths[0]);

    $this->actingAs($admin)
        ->get(route('order-returns.evidence', [$returnRequest, 0]))
        ->assertOk();

    $this->actingAs($admin)
        ->patch(route('order-returns.update', $returnRequest), [
            'status' => 'approved',
            'admin_note' => 'Pengajuan disetujui. Silakan kirim barang kembali.',
        ])
        ->assertRedirect();

    expect($returnRequest->fresh()->status)->toBe('approved');
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
