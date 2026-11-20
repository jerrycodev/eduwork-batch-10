<?php

use App\Models\User;

function precognitionHeaders(string $field): array
{
    return [
        'Precognition' => 'true',
        'Precognition-Validate-Only' => $field,
        'Accept' => 'application/json',
    ];
}

test('registration validates fields precognitively without creating an account', function () {
    $this->withHeaders(precognitionHeaders('email'))
        ->post(route('register'), [
            'name' => 'Preview Customer',
            'email' => 'not-an-email',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    $this->assertDatabaseMissing('users', ['email' => 'not-an-email']);
});

test('valid registration precognition returns success without executing registration', function () {
    $this->withHeaders(precognitionHeaders('email'))
        ->post(route('register'), [
            'name' => 'Preview Customer',
            'email' => 'preview@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertNoContent();

    $this->assertDatabaseMissing('users', ['email' => 'preview@example.com']);
});

test('customer profile supports live validation without mutating the profile', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    $this->actingAs($customer, 'customer')
        ->withHeaders(precognitionHeaders('email'))
        ->patch(route('profile.update'), [
            'name' => 'Changed Name',
            'email' => 'invalid-email',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    expect($customer->fresh()->name)->toBe($customer->name)
        ->and($customer->fresh()->email)->toBe($customer->email);
});

test('address form supports live validation without storing an address', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    $this->actingAs($customer, 'customer')
        ->withHeaders(precognitionHeaders('label'))
        ->post(route('addresses.store'), [
            'label' => '',
            'recipient_name' => 'Preview Recipient',
            'phone' => '08123456789',
            'address_line' => 'Jalan Contoh 1',
            'province_code' => '',
            'regency_code' => '',
            'district_code' => '',
            'village_code' => '',
            'postal_code' => '12345',
            'country' => 'Indonesia',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('label');

    expect($customer->addresses()->count())->toBe(0);
});

test('customer login supports live validation without authenticating', function () {
    $this->withHeaders(precognitionHeaders('email'))
        ->post(route('login'), [
            'email' => 'invalid-email',
            'password' => 'password',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    expect(auth('customer')->check())->toBeFalse();
});
