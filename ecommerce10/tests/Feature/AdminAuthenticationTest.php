<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('admin login page is separate from customer login', function () {
    $this->get('/admin/login')->assertOk()->assertSee('Administrator sign in');
    $this->get('/login')->assertOk()->assertDontSee('Administrator sign in');
});

test('authenticated customers visiting admin login are redirected to storefront home', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    $this->actingAs($customer, 'customer')
        ->get('/admin/login')
        ->assertRedirect(route('home'));
});

test('authenticated administrators visiting admin login are redirected to dashboard', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'admin')
        ->get('/admin/login')
        ->assertRedirect(route('dashboard'));
});

test('authenticated administrators visiting customer login are redirected to dashboard', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'admin')
        ->get('/login')
        ->assertRedirect(route('dashboard'));
});

test('administrators can authenticate only through the admin guard', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->post('/admin/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($admin, 'admin');
    $this->assertGuest('customer');
    $this->get(route('dashboard'))->assertOk();
});

test('admin login ignores stale customer intended URLs', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->withSession(['url.intended' => '/login'])
        ->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

    $this->assertAuthenticatedAs($admin, 'admin');
    $response->assertRedirect(route('dashboard'));
});

test('customers cannot authenticate through the admin login', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    $this->post('/admin/login', [
        'email' => $customer->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest('admin');
});

test('administrators cannot authenticate through the customer login', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->post('/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest('customer');
});

test('admin and customer sessions can be logged out independently', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $admin = User::factory()->create(['role' => 'admin']);

    $this->post('/login', ['email' => $customer->email, 'password' => 'password']);
    $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password']);

    $this->assertAuthenticatedAs($customer, 'customer');
    $this->assertAuthenticatedAs($admin, 'admin');

    $this->post('/logout')->assertRedirect('/');
    $this->assertGuest('customer');
    $this->assertAuthenticatedAs($admin, 'admin');

    $this->post('/login', ['email' => $customer->email, 'password' => 'password']);
    $this->post('/admin/logout')->assertRedirect(route('admin.login'));

    $this->assertAuthenticatedAs($customer, 'customer');
    $this->assertGuest('admin');
});

test('admin password reset uses its own broker and reset URL', function () {
    Notification::fake();

    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create(['role' => 'customer']);

    $this->post('/admin/forgot-password', ['email' => $admin->email])->assertSessionHas('status');
    Notification::assertSentTo($admin, ResetPassword::class, function (ResetPassword $notification) use ($admin) {
        $mail = $notification->toMail($admin);
        $this->post('/admin/reset-password', [
            'token' => $notification->token,
            'email' => $admin->email,
            'password' => 'AdminResetPassword123!',
            'password_confirmation' => 'AdminResetPassword123!',
        ])->assertRedirect(route('admin.login'));

        return str_contains($mail->viewData['resetUrl'], '/admin/reset-password/')
            && Hash::check('AdminResetPassword123!', $admin->fresh()->password);
    });

    $this->post('/admin/forgot-password', ['email' => $customer->email])->assertSessionHas('status');
    Notification::assertNotSentTo($customer, ResetPassword::class);
});

test('customer password reset does not send reset links to administrator accounts', function () {
    Notification::fake();

    $admin = User::factory()->create(['role' => 'admin']);

    $this->post('/forgot-password', ['email' => $admin->email])->assertSessionHas('status');

    Notification::assertNotSentTo($admin, ResetPassword::class);
});

test('storefront keeps using the customer identity when both guards are authenticated', function () {
    $customer = User::factory()->create(['name' => 'Store Customer Visible', 'role' => 'customer']);
    $admin = User::factory()->create(['name' => 'Admin Identity Hidden', 'role' => 'admin']);

    $this->post('/login', ['email' => $customer->email, 'password' => 'password']);
    $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password']);
    $this->get(route('dashboard'))->assertOk()->assertSee('Admin Identity Hidden');

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Store Customer Visible')
        ->assertDontSee('Admin Identity Hidden');
});

test('new accounts default to the customer role at database level', function () {
    $customer = User::query()->create([
        'name' => 'Default Customer',
        'email' => 'default-customer@example.com',
        'password' => Hash::make('password'),
    ]);

    expect($customer->fresh()->role)->toBe('customer');
});
