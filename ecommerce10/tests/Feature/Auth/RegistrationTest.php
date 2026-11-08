<?php

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});


test('new users are redirected to the requested local path after registration', function () {
    $response = $this->post('/register', [
        'name' => 'Redirect User',
        'email' => 'redirect-user@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'redirect_to' => '/cart',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/cart');
});

test('external redirect destinations are ignored after registration', function () {
    $response = $this->post('/register', [
        'name' => 'Safe Redirect User',
        'email' => 'safe-redirect-user@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'redirect_to' => '//evil.example/phishing',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});
