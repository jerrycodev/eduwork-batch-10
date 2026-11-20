<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('authenticated customers visiting the login page are redirected to storefront home', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    $this->actingAs($customer, 'customer')
        ->get('/login')
        ->assertRedirect(route('home'));
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('account', absolute: false));
});

test('customer login ignores stale admin intended URLs', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    $response = $this->withSession(['url.intended' => '/admin/login'])
        ->post('/login', [
            'email' => $customer->email,
            'password' => 'password',
        ]);

    $this->assertAuthenticatedAs($customer, 'customer');
    $response->assertRedirect(route('account', absolute: false));
});

test('customer login rejects admin destinations supplied as redirect_to', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    $response = $this->post('/login', [
        'email' => $customer->email,
        'password' => 'password',
        'redirect_to' => '/admin/login',
    ]);

    $this->assertAuthenticatedAs($customer, 'customer');
    $response->assertRedirect(route('account', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'customer')->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('users are redirected to the requested local path after login', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'redirect_to' => '/checkout?source=login',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/checkout?source=login');
});

test('external redirect destinations are ignored after login', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'redirect_to' => 'https://evil.example/phishing',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('account', absolute: false));
});
