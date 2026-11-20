<?php

use App\Models\CustomerActivityLog;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

test('successful customer login is recorded', function () {
    $customer = User::factory()->create([
        'role' => 'customer',
        'email' => 'activity-customer@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->post('/login', [
        'email' => $customer->email,
        'password' => 'password',
    ])->assertRedirect();

    $log = CustomerActivityLog::query()->sole();

    expect($log->user_id)->toBe($customer->id)
        ->and($log->action)->toBe('customer.login')
        ->and($log->method)->toBe('POST')
        ->and($log->response_status)->toBe(302)
        ->and($log->metadata)->toHaveKeys(['device_type', 'platform', 'browser'])
        ->and($log->metadata)->not->toHaveKey('password');
});

test('adding a product to cart is not recorded in customer activity history', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $category = ProductCategory::query()->create(['name' => 'Activity Category', 'slug' => 'activity-category']);
    $product = Product::query()->create([
        'name' => 'Activity Product', 'slug' => 'activity-product', 'description' => 'Test product',
        'image' => 'products/test.jpg', 'price' => 25000, 'stock' => 8, 'category_id' => $category->id,
    ]);

    $this->actingAs($customer, 'customer')
        ->post(route('cart.store', $product), ['quantity' => 2])
        ->assertRedirect(route('cart'));

    expect(CustomerActivityLog::query()->count())->toBe(0);
});

test('customer mutations are recorded without request payloads', function () {
    Notification::fake();
    $customer = User::factory()->unverified()->create();

    $this->actingAs($customer, 'customer')
        ->post(route('verification.send'))
        ->assertRedirect();

    Notification::assertSentTo($customer, VerifyEmail::class);

    $log = CustomerActivityLog::query()->sole();

    expect($log->user_id)->toBe($customer->id)
        ->and($log->action)->toBe('email.verification_resent')
        ->and($log->route_name)->toBe('verification.send')
        ->and($log->method)->toBe('POST')
        ->and($log->metadata)->not->toHaveKey('password');
});

test('customer logout is recorded even after the session is invalidated', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    $this->actingAs($customer, 'customer')
        ->post('/logout')
        ->assertRedirect('/');

    expect(CustomerActivityLog::query()->sole()->action)->toBe('logout');
});

test('customer activity log page is restricted to administrators and supports filters', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create(['role' => 'customer']);

    CustomerActivityLog::query()->create([
        'user_id' => $customer->id,
        'actor_name' => $customer->name,
        'actor_email' => $customer->email,
        'action' => 'customer.login',
        'route_name' => 'login',
        'method' => 'POST',
        'response_status' => 302,
        'ip_address' => '127.0.0.1',
        'metadata' => ['path' => '/login'],
        'created_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('customer-activity-logs.index', ['search' => $customer->email, 'action' => 'customer.login']))
        ->assertOk()
        ->assertSee('Customer Activity Logs')
        ->assertSee($customer->email)
        ->assertSee('customer.login');

    auth('admin')->logout();

    $this->actingAs($customer, 'customer')
        ->get(route('customer-activity-logs.index'))
        ->assertRedirect(route('admin.login'));
});
