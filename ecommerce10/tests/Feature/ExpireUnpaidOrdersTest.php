<?php

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderAlert;
use App\Services\MidtransPaymentService;
use Illuminate\Support\Facades\Notification;

function expiringOrder(User $customer, array $attributes = []): Order
{
    return Order::query()->create(array_merge([
        'user_id' => $customer->id,
        'user_name' => $customer->name,
        'user_email' => $customer->email,
        'total' => 120000,
        'status' => 'pending',
        'payment_status' => 'pending',
        'payment_expires_at' => now()->subMinute(),
    ], $attributes));
}

test('unpaid order without a Midtrans transaction expires and releases reservation automatically', function () {
    Notification::fake();
    $customer = User::factory()->create();
    User::factory()->create(['role' => 'admin']);
    $order = expiringOrder($customer);

    $payments = Mockery::mock(MidtransPaymentService::class);
    $payments->shouldNotReceive('fetchStatus');
    $payments->shouldNotReceive('cancelPending');
    $this->app->instance(MidtransPaymentService::class, $payments);

    $this->artisan('orders:expire-unpaid')->assertSuccessful();

    expect($order->fresh()->status)->toBe('cancelled')
        ->and($order->fresh()->payment_status)->toBe('expired')
        ->and($order->fresh()->stock_restored_at)->not->toBeNull()
        ->and($order->fresh()->cancellationRequest->status)->toBe('completed');

    Notification::assertSentTo($customer, OrderAlert::class);
});

test('pending Midtrans transaction is cancelled and verified before stock reservation is released', function () {
    Notification::fake();
    $customer = User::factory()->create();
    $order = expiringOrder($customer, ['payment_order_id' => 'PAY-EXPIRY-'.uniqid()]);

    $payments = Mockery::mock(MidtransPaymentService::class);
    $payments->shouldReceive('fetchStatus')->twice()->andReturn(
        (object) ['transaction_status' => 'pending'],
        (object) ['transaction_status' => 'cancel'],
    );
    $payments->shouldReceive('cancelPending')->once()->with(Mockery::on(fn (Order $candidate): bool => $candidate->is($order)))
        ->andReturn((object) ['transaction_status' => 'cancel']);
    $this->app->instance(MidtransPaymentService::class, $payments);

    $this->artisan('orders:expire-unpaid')->assertSuccessful();

    expect($order->fresh()->status)->toBe('cancelled')
        ->and($order->fresh()->payment_status)->toBe('cancelled')
        ->and($order->fresh()->stock_restored_at)->not->toBeNull();
});

test('settled Midtrans transaction is never cancelled or released by expiry command', function () {
    $customer = User::factory()->create();
    $order = expiringOrder($customer, ['payment_order_id' => 'PAY-SETTLED-'.uniqid()]);

    $payments = Mockery::mock(MidtransPaymentService::class);
    $payments->shouldReceive('fetchStatus')->once()->andReturn((object) ['transaction_status' => 'settlement']);
    $payments->shouldNotReceive('cancelPending');
    $this->app->instance(MidtransPaymentService::class, $payments);

    $this->artisan('orders:expire-unpaid')->assertSuccessful();

    expect($order->fresh()->status)->toBe('pending')
        ->and($order->fresh()->payment_status)->toBe('pending')
        ->and($order->fresh()->stock_restored_at)->toBeNull();
});

test('orders with an unexpired payment reservation are left untouched', function () {
    $customer = User::factory()->create();
    $order = expiringOrder($customer, ['payment_expires_at' => now()->addMinutes(10)]);

    $payments = Mockery::mock(MidtransPaymentService::class);
    $payments->shouldNotReceive('fetchStatus');
    $this->app->instance(MidtransPaymentService::class, $payments);

    $this->artisan('orders:expire-unpaid')->assertSuccessful();

    expect($order->fresh()->status)->toBe('pending')
        ->and($order->fresh()->stock_restored_at)->toBeNull();
});

test('Midtrans transaction not found response expires the stale reservation and restores stock', function () {
    Notification::fake();
    $customer = User::factory()->create();
    $order = expiringOrder($customer, ['payment_order_id' => 'PAY-MISSING-'.uniqid()]);

    $payments = Mockery::mock(MidtransPaymentService::class);
    $payments->shouldReceive('fetchStatus')->once()
        ->andThrow(new RuntimeException("Midtrans API is returning API error. HTTP status code: 404 API response: Transaction doesn't exist.", 404));
    $payments->shouldNotReceive('cancelPending');
    $this->app->instance(MidtransPaymentService::class, $payments);

    $this->artisan('orders:expire-unpaid')->assertSuccessful();

    expect($order->fresh()->status)->toBe('cancelled')
        ->and($order->fresh()->payment_status)->toBe('expired')
        ->and($order->fresh()->stock_restored_at)->not->toBeNull();
});
