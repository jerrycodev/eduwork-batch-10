<?php

use App\Models\User;

test('customer profile page uses the customer storefront layout and customer settings', function () {
    $user = User::factory()->create(['role' => 'customer']);

    $this->actingAs($user, 'customer')
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Profil Saya')
        ->assertSee('Alamat Pengiriman')
        ->assertDontSee('Admin Profile')
        ->assertDontSee('Admin workspace');
});

test('admin profile page uses the admin dashboard layout and separate admin settings', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.profile.edit'))
        ->assertOk()
        ->assertSee('Admin Profile')
        ->assertSee('Admin panel')
        ->assertDontSee('Alamat Pengiriman')
        ->assertSee(route('admin.profile.update'), false)
        ->assertSee(route('admin.profile.destroy'), false);
});

test('admin visiting the customer profile URL is redirected to the admin profile', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'admin')
        ->get(route('profile.edit'))
        ->assertRedirect(route('login'));
});

test('customers cannot access the admin profile page', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    $this->actingAs($customer, 'customer')
        ->get(route('admin.profile.edit'))
        ->assertRedirect(route('admin.login'));
});

test('customer profile information can be updated', function () {
    $user = User::factory()->create(['role' => 'customer']);

    $this->actingAs($user, 'customer')
        ->patch(route('profile.update'), [
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh()->name)->toBe('Test Customer')
        ->and($user->fresh()->email)->toBe('customer@example.com')
        ->and($user->fresh()->email_verified_at)->toBeNull();
});

test('admin profile information can be updated from the admin profile route', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'admin')
        ->patch(route('admin.profile.update'), [
            'name' => 'Store Administrator',
            'email' => 'admin-updated@example.com',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.profile.edit'));

    expect($admin->fresh()->name)->toBe('Store Administrator')
        ->and($admin->fresh()->email)->toBe('admin-updated@example.com');
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create(['role' => 'customer']);

    $this->actingAs($user, 'customer')
        ->patch(route('profile.update'), [
            'name' => 'Test Customer',
            'email' => $user->email,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create(['role' => 'customer']);

    $this->actingAs($user, 'customer')
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    expect($user->fresh())->toBeNull();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create(['role' => 'customer']);

    $this->actingAs($user, 'customer')
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), ['password' => 'wrong-password'])
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});
