<?php

use App\Models\Order;
use App\Models\PaymentReconciliationAlert;
use App\Models\PaymentReconciliationCheck;
use App\Models\PaymentStatusResolution;
use App\Models\User;
use App\Notifications\OrderAlert;
use App\Services\MidtransPaymentService;
use App\Services\PaymentReconciliationService;
use Illuminate\Support\Facades\Notification;

function reconciliationTestOrder(User $user, array $attributes = []): Order
{
    return Order::query()->create(array_merge([
        'user_id' => $user->id,
        'user_name' => $user->name,
        'user_email' => $user->email,
        'total' => 150000,
        'status' => 'pending',
        'payment_status' => 'pending',
        'payment_order_id' => 'PAY-'.uniqid(),
    ], $attributes));
}

test('reconciliation detects fulfillment that has no verified payment', function () {
    $user = User::factory()->create();
    $order = reconciliationTestOrder($user, ['status' => 'shipped', 'payment_status' => 'pending']);

    $findings = app(PaymentReconciliationService::class)->findings();
    $finding = $findings->firstWhere('order.id', $order->id);

    expect($finding)->not->toBeNull()
        ->and($finding['severity'])->toBe('critical')
        ->and($finding['code'])->toBe('fulfillment_unpaid');
});

test('reconciliation flags paid orders with missing transaction evidence and late payment after cancellation', function () {
    $user = User::factory()->create();
    $order = reconciliationTestOrder($user, [
        'status' => 'cancelled',
        'payment_status' => 'paid',
        'transaction_id' => null,
        'paid_at' => null,
    ]);

    $findings = app(PaymentReconciliationService::class)->findings()->where('order.id', $order->id);

    expect($findings->pluck('code')->all())->toContain('paid_missing_evidence', 'paid_cancelled_order');
});

test('admin can compare a Midtrans status response without automatically changing local payment state', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $order = reconciliationTestOrder($admin, [
        'payment_status' => 'paid',
        'transaction_id' => 'midtrans-transaction-1',
        'paid_at' => now(),
        'payment_payload' => ['transaction_status' => 'settlement', 'gross_amount' => '150000.00'],
    ]);

    $payments = Mockery::mock(MidtransPaymentService::class);
    $payments->shouldReceive('fetchStatus')->once()->with(Mockery::on(fn (Order $candidate): bool => $candidate->is($order)))
        ->andReturn((object) [
            'order_id' => $order->payment_order_id,
            'transaction_id' => 'midtrans-transaction-1',
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'gross_amount' => '150000.00',
        ]);
    $this->app->instance(MidtransPaymentService::class, $payments);

    $this->actingAs($admin, 'admin')
        ->post(route('payment-reconciliation.check', $order))
        ->assertRedirect()
        ->assertSessionHas('status');

    $check = PaymentReconciliationCheck::query()->sole();
    expect($check->result)->toBe('matched')
        ->and($check->remote_status)->toBe('settlement')
        ->and($order->fresh()->payment_status)->toBe('paid');
});

test('Midtrans amount mismatch is recorded and local status is not overwritten', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $order = reconciliationTestOrder($admin, ['payment_status' => 'pending']);
    $payments = Mockery::mock(MidtransPaymentService::class);
    $payments->shouldReceive('fetchStatus')->once()->andReturn((object) [
        'order_id' => $order->payment_order_id,
        'transaction_id' => 'midtrans-transaction-2',
        'transaction_status' => 'settlement',
        'fraud_status' => 'accept',
        'gross_amount' => '149999.99',
    ]);
    $this->app->instance(MidtransPaymentService::class, $payments);

    $this->actingAs($admin, 'admin')
        ->post(route('payment-reconciliation.check', $order))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(PaymentReconciliationCheck::query()->sole()->result)->toBe('mismatch')
        ->and($order->fresh()->payment_status)->toBe('pending');
});

test('payment reconciliation page is restricted to administrators', function () {
    $this->actingAs(User::factory()->create(['role' => 'customer']), 'customer')
        ->get(route('payment-reconciliation.index'))
        ->assertRedirect(route('admin.login'));
});

test('admin can open payment reconciliation dashboard and see local findings', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $order = reconciliationTestOrder($admin, ['status' => 'processing', 'payment_status' => 'pending']);

    $this->actingAs($admin, 'admin')
        ->get(route('payment-reconciliation.index'))
        ->assertOk()
        ->assertSee('Payment Reconciliation')
        ->assertSee('Fulfillment tanpa pembayaran terverifikasi')
        ->assertSee($order->order_number);
});

test('scheduled reconciliation checks recent Midtrans payments and notifies admins once per unchanged anomaly', function () {
    Notification::fake();
    $admin = User::factory()->create(['role' => 'admin']);
    $order = reconciliationTestOrder($admin, ['payment_status' => 'pending']);
    $payments = Mockery::mock(MidtransPaymentService::class);
    $payments->shouldReceive('fetchStatus')->twice()->andReturn((object) [
        'order_id' => $order->payment_order_id,
        'transaction_id' => 'midtrans-scheduled-1',
        'transaction_status' => 'settlement',
        'fraud_status' => 'accept',
        'gross_amount' => '150000.00',
    ]);
    $this->app->instance(MidtransPaymentService::class, $payments);

    $this->artisan('payments:reconcile')->assertSuccessful();
    $this->travel(7)->hours();
    $this->artisan('payments:reconcile')->assertSuccessful();

    expect(PaymentReconciliationCheck::query()->where('order_id', $order->id)->count())->toBe(2)
        ->and($order->fresh()->payment_status)->toBe('pending')
        ->and(PaymentReconciliationAlert::query()->where('order_id', $order->id)->count())->toBe(1);

    Notification::assertSentToTimes($admin, OrderAlert::class, 1);
});

test('scheduled reconciliation skips orders checked within the configured interval', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $order = reconciliationTestOrder($admin);
    PaymentReconciliationCheck::query()->create([
        'order_id' => $order->id,
        'result' => 'matched',
        'local_status' => 'pending',
        'checked_at' => now()->subHour(),
    ]);

    $payments = Mockery::mock(MidtransPaymentService::class);
    $payments->shouldNotReceive('fetchStatus');
    $this->app->instance(MidtransPaymentService::class, $payments);

    $this->artisan('payments:reconcile')->assertSuccessful();

    expect(PaymentReconciliationCheck::query()->where('order_id', $order->id)->count())->toBe(1);
});

test('admin can apply a freshly verified Midtrans settlement with an audited reason without changing fulfillment', function () {
    Notification::fake();
    $admin = User::factory()->create(['role' => 'admin']);
    $order = reconciliationTestOrder($admin, [
        'status' => 'pending',
        'payment_status' => 'failed',
        'transaction_id' => null,
    ]);
    $payments = Mockery::mock(MidtransPaymentService::class);
    $payments->shouldReceive('fetchStatus')->once()->andReturn((object) [
        'order_id' => $order->payment_order_id,
        'transaction_id' => 'midtrans-verified-99',
        'transaction_status' => 'settlement',
        'fraud_status' => 'accept',
        'payment_type' => 'bank_transfer',
        'gross_amount' => '150000.00',
    ]);
    $this->app->instance(MidtransPaymentService::class, $payments);

    $this->actingAs($admin, 'admin')
        ->post(route('payment-reconciliation.resolve', $order), [
            'reason' => 'Settlement sudah diverifikasi langsung melalui Midtrans.',
            'expected_local_status' => 'failed',
            'confirm' => '1',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $resolution = PaymentStatusResolution::query()->sole();
    expect($order->fresh()->payment_status)->toBe('paid')
        ->and($order->fresh()->status)->toBe('pending')
        ->and($order->fresh()->transaction_id)->toBe('midtrans-verified-99')
        ->and($resolution->admin_id)->toBe($admin->id)
        ->and($resolution->previous_status)->toBe('failed')
        ->and($resolution->new_status)->toBe('paid')
        ->and($resolution->reason)->toContain('Settlement sudah diverifikasi');
});

test('payment resolution rejects amount mismatch and unsupported remote statuses without changing the order', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $order = reconciliationTestOrder($admin, ['payment_status' => 'pending']);
    $payments = Mockery::mock(MidtransPaymentService::class);
    $payments->shouldReceive('fetchStatus')->once()->andReturn((object) [
        'order_id' => $order->payment_order_id,
        'transaction_id' => 'midtrans-invalid-1',
        'transaction_status' => 'settlement',
        'fraud_status' => 'accept',
        'gross_amount' => '1.00',
    ]);
    $this->app->instance(MidtransPaymentService::class, $payments);

    $this->actingAs($admin, 'admin')
        ->post(route('payment-reconciliation.resolve', $order), [
            'reason' => 'Mencoba koreksi setelah pemeriksaan transaksi.',
            'expected_local_status' => 'pending',
            'confirm' => '1',
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($order->fresh()->payment_status)->toBe('pending')
        ->and(PaymentStatusResolution::query()->count())->toBe(0);
});

test('payment resolution rejects stale local status and missing admin confirmation', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $order = reconciliationTestOrder($admin, ['payment_status' => 'pending']);

    $this->actingAs($admin, 'admin')
        ->post(route('payment-reconciliation.resolve', $order), [
            'reason' => 'Status pembayaran sudah berubah sebelumnya.',
            'expected_local_status' => 'failed',
        ])
        ->assertSessionHasErrors(['confirm']);

    expect($order->fresh()->payment_status)->toBe('pending')
        ->and(PaymentStatusResolution::query()->count())->toBe(0);
});

test('scheduled Midtrans reconciliation also cancels orders when expiry callback was missed', function () {
    Notification::fake();
    $customer = User::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $order = reconciliationTestOrder($customer);
    $payments = Mockery::mock(MidtransPaymentService::class);
    $payments->shouldReceive('fetchStatus')->once()->with(Mockery::on(fn (Order $candidate): bool => $candidate->is($order)))
        ->andReturn((object) [
            'order_id' => $order->payment_order_id,
            'transaction_id' => 'midtrans-expired-fallback',
            'transaction_status' => 'expire',
            'gross_amount' => '150000.00',
        ]);
    $this->app->instance(MidtransPaymentService::class, $payments);

    $this->artisan('payments:reconcile')->assertSuccessful();

    expect($order->fresh()->payment_status)->toBe('expired')
        ->and($order->fresh()->status)->toBe('cancelled')
        ->and($order->fresh()->cancellationRequest->status)->toBe('completed')
        ->and(PaymentReconciliationCheck::query()->sole()->result)->toBe('matched');

    Notification::assertSentTo($customer, OrderAlert::class);
    Notification::assertSentTo($admin, OrderAlert::class);
});
