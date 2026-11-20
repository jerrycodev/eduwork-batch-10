<?php

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderAlert;

function deliveryFollowUpOrder(User $customer, array $attributes = []): Order
{
    return Order::query()->create(array_merge([
        'user_id' => $customer->id,
        'user_name' => $customer->name,
        'user_email' => $customer->email,
        'total' => 125000,
        'status' => 'shipped',
        'payment_status' => 'paid',
        'tracking_number' => 'JNE-DELIVERY-123',
        'shipped_at' => now()->subDays(3),
        'estimated_delivery_at' => now()->subDay(),
    ], $attributes));
}

test('admin receives a delivery follow-up notification for shipped orders past ETA', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create();
    $order = deliveryFollowUpOrder($customer);

    $this->artisan('orders:remind-overdue-deliveries')->assertSuccessful();

    expect($admin->notifications()->where('type', OrderAlert::class)->count())->toBe(1)
        ->and($admin->notifications()->first()->data['type'])->toBe('delivery_follow_up')
        ->and($admin->notifications()->first()->data['url'])->toBe('/dashboard/orders/'.$order->id)
        ->and($order->fresh()->delivery_follow_up_reminder_count)->toBe(1)
        ->and($order->fresh()->delivery_follow_up_last_notified_at)->not->toBeNull();
});

test('delivery reminder is sent at most once per day and repeats on the next day', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create();
    $order = deliveryFollowUpOrder($customer);

    $this->artisan('orders:remind-overdue-deliveries')->assertSuccessful();
    $this->artisan('orders:remind-overdue-deliveries')->assertSuccessful();

    expect($admin->notifications()->where('type', OrderAlert::class)->count())->toBe(1);

    $order->update(['delivery_follow_up_last_notified_at' => now()->subDay()]);
    $this->artisan('orders:remind-overdue-deliveries')->assertSuccessful();

    expect($admin->notifications()->where('type', OrderAlert::class)->count())->toBe(2)
        ->and($order->fresh()->delivery_follow_up_reminder_count)->toBe(2);
});

test('delivery reminder ignores orders that are not shipped or have no ETA', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create();
    deliveryFollowUpOrder($customer, ['status' => 'delivered']);
    deliveryFollowUpOrder($customer, ['estimated_delivery_at' => null]);

    $this->artisan('orders:remind-overdue-deliveries')->assertSuccessful();

    expect($admin->notifications()->count())->toBe(0);
});

test('admin orders page shows delivery metrics and can filter delivery follow-ups', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create();
    $overdueOrder = deliveryFollowUpOrder($customer, ['estimated_delivery_at' => now()->subDay()]);
    deliveryFollowUpOrder($customer, ['estimated_delivery_at' => now()]);

    $this->actingAs($admin, 'admin')
        ->get(route('orders.index'))
        ->assertOk()
        ->assertSee('In delivery')
        ->assertSee('Estimated today')
        ->assertSee('Delivery overdue');

    $this->get(route('orders.index', ['follow_up' => 'delivery']))
        ->assertOk()
        ->assertSee($overdueOrder->order_number);
});

test('Biteship webhook records estimated delivery using the maximum ETD day', function () {
    config(['biteship.webhook.signature_key' => 'X-Biteship-Signature', 'biteship.webhook.signature_secret' => 'test-secret']);
    $customer = User::factory()->create();
    $order = deliveryFollowUpOrder($customer, [
        'status' => 'processing',
        'shipped_at' => null,
        'estimated_delivery_at' => null,
        'shipping_etd' => '1 - 2 days',
        'biteship_order_id' => 'bite-eta-123',
    ]);

    $this->postJson(route('webhooks.biteship'), [
        'event' => 'order.status', 'order_id' => 'bite-eta-123', 'status' => 'picked',
    ], ['X-Biteship-Signature' => 'test-secret'])->assertOk();

    expect($order->fresh()->estimated_delivery_at)->not->toBeNull()
        ->and($order->fresh()->estimated_delivery_at->toDateString())->toBe(now()->addDays(2)->toDateString());
});
