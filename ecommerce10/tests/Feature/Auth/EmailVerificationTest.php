<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

test('unverified customer sees the email verification banner in the storefront header', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user, 'customer')
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Verifikasi email Anda')
        ->assertSee('Kirim ulang email')
        ->assertSee($user->email);
});

test('verified customer does not see the email verification banner', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'customer')
        ->get(route('home'))
        ->assertOk()
        ->assertDontSee('Verifikasi email Anda');
});

test('email verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user, 'customer')->get('/verify-email');

    $response->assertStatus(200);
});

test('email can be verified', function () {
    $user = User::factory()->unverified()->create();

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    // A stale intended URL must not send the customer to an unrelated or forbidden order page.
    $response = $this->withSession(['url.intended' => '/orders/another-customers-order'])
        ->actingAs($user, 'customer')
        ->get($verificationUrl);

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('orders.customer.index'));
    $response->assertSessionHas('verified', 1);
});

test('unverified customers cannot access checkout or submit checkout requests', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user, 'customer')
        ->get('/checkout')
        ->assertRedirect(route('verification.notice'));

    $this->actingAs($user, 'customer')
        ->post('/checkout/shipping-rates', [])
        ->assertRedirect(route('verification.notice'));

    $this->actingAs($user, 'customer')
        ->post('/checkout', [])
        ->assertRedirect(route('verification.notice'));
});

test('verified customers can access the checkout route', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'customer')
        ->get('/checkout')
        ->assertRedirect(route('cart'));
});

test('email is not verified with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    $this->actingAs($user, 'customer')->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});
