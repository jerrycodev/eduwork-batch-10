<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new customers receive an email verification notification after registration', function () {
    Notification::fake();

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'test@example.com')->firstOrFail();

    $this->assertAuthenticatedAs($user, 'customer');
    $response->assertRedirect(route('verification.notice'));
    $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'email_verified_at' => null]);
    Notification::assertSentTo($user, VerifyEmail::class);
});

test('new customers are sent to email verification even when a redirect path was requested', function () {
    $response = $this->post('/register', [
        'name' => 'Redirect User',
        'email' => 'redirect-user@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'redirect_to' => '/cart',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('verification.notice'));
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
    $response->assertRedirect(route('verification.notice'));
});
