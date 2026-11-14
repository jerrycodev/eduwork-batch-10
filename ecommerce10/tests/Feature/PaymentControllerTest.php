<?php

use App\Models\Order;
use App\Models\User;

test('opening the legacy checkout URL redirects to the cart instead of creating a demo payment', function () {
    $this->get(route('checkout'))
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
});

test('midtrans callback endpoint is exempt from browser CSRF validation', function () {
    $this->postJson(route('midtrans.callback'), [])
        ->assertOk()
        ->assertJson(['status' => 'ignored', 'reason' => 'incomplete_payload']);
});

test('verified Midtrans settlement updates payment status but leaves fulfillment status for admin', function () {
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
    expect($order->payment_status)->toBe('paid')
        ->and($order->status)->toBe('pending')
        ->and($order->transaction_id)->toBe('midtrans-transaction-123')
        ->and($order->payment_type)->toBe('gopay')
        ->and($order->paid_at)->not->toBeNull();
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
    config(['midtrans.server_key' => $serverKey]);
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

    expect($order->fresh()->payment_status)->toBe('pending');
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

    $this->actingAs($admin)
        ->patch(route('orders.status.update', $order), ['status' => 'processing'])
        ->assertSessionHasErrors('status');
    expect($order->fresh()->status)->toBe('pending');

    $order->update(['payment_status' => 'paid']);
    $this->patch(route('orders.status.update', $order), ['status' => 'processing'])
        ->assertRedirect(route('orders.show', $order));
    expect($order->fresh()->status)->toBe('processing');
});
