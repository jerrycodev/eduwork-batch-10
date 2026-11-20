<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('administrators can browse and search users', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create([
        'name' => 'Nadia Store Customer',
        'email' => 'nadia@example.test',
        'role' => 'customer',
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin-users.index', ['search' => 'nadia']))
        ->assertOk()
        ->assertSee('Manajemen Pengguna')
        ->assertSee('Nadia Store Customer')
        ->assertSee('nadia@example.test')
        ->assertDontSee('someone-else@example.test');
});

test('administrators can create users with a selected role', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'admin')
        ->post(route('admin-users.store'), [
            'name' => 'New Store Customer',
            'email' => 'new-customer@example.test',
            'role' => 'customer',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ])
        ->assertRedirect(route('admin-users.index'));

    $createdUser = User::query()->where('email', 'new-customer@example.test')->sole();

    expect($createdUser->name)->toBe('New Store Customer')
        ->and($createdUser->role)->toBe('customer')
        ->and(Hash::check('StrongPassword123!', $createdUser->password))->toBeTrue();
});

test('administrators can update a user role and reset password', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create(['role' => 'customer']);

    $this->actingAs($admin, 'admin')
        ->put(route('admin-users.update', $customer), [
            'name' => 'Updated Customer',
            'email' => $customer->email,
            'role' => 'admin',
            'password' => 'AnotherStrong123!',
            'password_confirmation' => 'AnotherStrong123!',
        ])
        ->assertRedirect(route('admin-users.index'));

    expect($customer->fresh()->name)->toBe('Updated Customer')
        ->and($customer->fresh()->role)->toBe('admin')
        ->and(Hash::check('AnotherStrong123!', $customer->fresh()->password))->toBeTrue();
});

test('administrators cannot demote themselves or remove the final administrator', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'admin')
        ->put(route('admin-users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'customer',
        ])
        ->assertSessionHasErrors('role');

    $this->actingAs($admin, 'admin')
        ->delete(route('admin-users.destroy', $admin))
        ->assertSessionHas('error');

    expect($admin->fresh()->role)->toBe('admin');
});

test('administrators can delete another customer without dependent orders or carts', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create(['role' => 'customer']);

    $this->actingAs($admin, 'admin')
        ->delete(route('admin-users.destroy', $customer))
        ->assertRedirect(route('admin-users.index'));

    expect(User::query()->find($customer->id))->toBeNull();
});

test('customers cannot access user management', function () {
    $customer = User::factory()->create(['role' => 'customer']);

    $this->actingAs($customer, 'customer')
        ->get(route('admin-users.index'))
        ->assertRedirect(route('admin.login'));
});
