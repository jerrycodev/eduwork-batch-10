<?php

use App\Models\User;
use App\Notifications\OrderAlert;
use Illuminate\Support\Str;

test('customer notifications use the storefront notification page', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $customer->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => OrderAlert::class,
        'data' => ['title' => 'Pembayaran berhasil', 'message' => 'Pesanan Anda sudah dibayar.'],
    ]);

    $this->actingAs($customer, 'customer')
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('Notifikasi')
        ->assertSee('Pembayaran berhasil')
        ->assertDontSee('Admin workspace');
});

test('admin is redirected from customer notifications to the admin notification page', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'admin')
        ->get(route('notifications.index'))
        ->assertRedirect(route('login'));
});

test('admin notifications use the dashboard and customer cannot access them', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create(['role' => 'customer']);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.notifications.index'))
        ->assertOk()
        ->assertSee('Admin panel')
        ->assertSee('All notifications')
        ->assertDontSee('Alamat Pengiriman');

    auth('admin')->logout();

    $this->actingAs($customer, 'customer')
        ->get(route('admin.notifications.index'))
        ->assertRedirect(route('admin.login'));
});

test('admin can mark only their own notification as read', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $otherAdmin = User::factory()->create(['role' => 'admin']);

    $notification = $admin->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => OrderAlert::class,
        'data' => ['title' => 'Pesanan baru', 'message' => 'Ada pesanan baru.'],
    ]);

    $foreignNotification = $otherAdmin->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => OrderAlert::class,
        'data' => ['title' => 'Notifikasi lain', 'message' => 'Notifikasi admin lain.'],
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.notifications.read', $foreignNotification->id))
        ->assertNotFound();

    expect($foreignNotification->fresh()->read_at)->toBeNull();

    $this->post(route('admin.notifications.read', $notification->id))
        ->assertRedirect(route('admin.notifications.index'));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('admin can mark all their unread notifications as read', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $otherAdmin = User::factory()->create(['role' => 'admin']);

    $notification = $admin->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => OrderAlert::class,
        'data' => ['title' => 'Pesanan baru'],
    ]);
    $otherNotification = $otherAdmin->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => OrderAlert::class,
        'data' => ['title' => 'Notifikasi lain'],
    ]);

    $this->actingAs($admin, 'admin')
        ->from(route('admin.notifications.index'))
        ->post(route('admin.notifications.read-all'))
        ->assertRedirect(route('admin.notifications.index'));

    expect($notification->fresh()->read_at)->not->toBeNull()
        ->and($otherNotification->fresh()->read_at)->toBeNull();
});

test('notification redirect uses a local path and preserves the current host', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $notification = $customer->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => OrderAlert::class,
        'data' => [
            'title' => 'Pembayaran berhasil',
            'url' => '/orders/01a1d968-6eb1-73c7-9f7d-f7cfe6b908a5',
        ],
    ]);

    $this->actingAs($customer, 'customer')
        ->post(route('notifications.read', $notification->id))
        ->assertRedirect(url('/orders/01a1d968-6eb1-73c7-9f7d-f7cfe6b908a5'));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('legacy absolute notification urls redirect to the same path on the current host', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $notification = $customer->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => OrderAlert::class,
        'data' => [
            'title' => 'Pembayaran berhasil',
            'url' => 'https://dev8000.jerrycodev.my.id/orders/legacy-order?tab=payment',
        ],
    ]);

    $this->actingAs($customer, 'customer')
        ->post(route('notifications.read', $notification->id))
        ->assertRedirect(url('/orders/legacy-order?tab=payment'));
});

test('external notification urls cannot redirect users away from the current host', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $notification = $customer->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => OrderAlert::class,
        'data' => [
            'title' => 'Invalid link',
            'url' => 'https://attacker.example/phishing',
        ],
    ]);

    $this->actingAs($customer, 'customer')
        ->post(route('notifications.read', $notification->id))
        ->assertRedirect(url('/phishing'));
});
