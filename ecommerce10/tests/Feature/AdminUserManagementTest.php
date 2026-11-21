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
    User::factory()->create(['role' => 'customer', 'is_active' => false]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin-users.index', ['search' => 'nadia']))
        ->assertOk()
        ->assertSee('Manajemen Pengguna')
        ->assertSee('Diblokir / Nonaktif')
        ->assertSee('Nadia Store Customer')
        ->assertSee('nadia@example.test')
        ->assertViewHas('stats', fn (array $stats) => $stats['inactive'] === 1)
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
        ->and($createdUser->email_verified_at)->toBeNull()
        ->and(Hash::check('StrongPassword123!', $createdUser->password))->toBeTrue();
});

test('administrators can mark a newly created user email as verified', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'admin')
        ->get(route('admin-users.create'))
        ->assertOk()
        ->assertSee('name="email_verified"', false)
        ->assertSee('Tandai email sebagai terverifikasi');

    $this->actingAs($admin, 'admin')
        ->post(route('admin-users.store'), [
            'name' => 'Verified New Customer',
            'email' => 'verified-new-customer@example.test',
            'role' => 'customer',
            'email_verified' => '1',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ])
        ->assertRedirect(route('admin-users.index'));

    expect(User::query()->where('email', 'verified-new-customer@example.test')->sole()->email_verified_at)
        ->not->toBeNull();
});

test('administrators can create another administrator when that role is selected', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'admin')
        ->get(route('admin-users.create'))
        ->assertOk()
        ->assertSee('<option value="admin"', false)
        ->assertSee('Administrator')
        ->assertSee('id="role-value" type="hidden" name="role" value="customer"', false)
        ->assertSee("#role-value').value = this.querySelector('#role').value", false);

    $this->actingAs($admin, 'admin')
        ->post(route('admin-users.store'), [
            'name' => 'New Administrator',
            'email' => 'new-admin@example.test',
            'role' => 'admin',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ])
        ->assertRedirect(route('admin-users.index'));

    expect(User::query()->where('email', 'new-admin@example.test')->sole()->role)
        ->toBe('admin');
});

test('new administrator accounts record the administrator who created them', function () {
    $creator = User::factory()->create(['role' => 'admin']);

    $this->actingAs($creator, 'admin')
        ->post(route('admin-users.store'), [
            'name' => 'Owned Administrator',
            'email' => 'owned-admin@example.test',
            'role' => 'admin',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ])
        ->assertRedirect(route('admin-users.index'));

    $createdAdmin = User::query()->where('email', 'owned-admin@example.test')->sole();

    expect($createdAdmin->created_by)->toBe($creator->id)
        ->and($createdAdmin->creator->is($creator))->toBeTrue();
});

test('only the creator can manage a directly created administrator', function () {
    $admin1 = User::factory()->create(['role' => 'admin']);
    $admin2 = User::factory()->create(['role' => 'admin', 'created_by' => $admin1->id]);

    $this->actingAs($admin2, 'admin')
        ->patch(route('admin-users.toggle-status', $admin1))
        ->assertSessionHas('error', 'Anda tidak memiliki izin untuk mengelola administrator ini.');

    $this->actingAs($admin2, 'admin')
        ->delete(route('admin-users.destroy', $admin1))
        ->assertSessionHas('error', 'Anda tidak memiliki izin untuk mengelola administrator ini.');

    $this->actingAs($admin2, 'admin')
        ->get(route('admin-users.edit', $admin1))
        ->assertRedirect(route('admin-users.index'));

    $this->actingAs($admin2, 'admin')
        ->put(route('admin-users.update', $admin1), [
            'name' => 'Hijacked Admin',
            'email' => $admin1->email,
            'role' => 'admin',
        ])
        ->assertRedirect(route('admin-users.index'))
        ->assertSessionHas('error', 'Anda tidak memiliki izin untuk mengelola administrator ini.');

    expect($admin1->fresh()->name)->not->toBe('Hijacked Admin')
        ->and($admin1->fresh()->is_active)->toBeTrue()
        ->and($admin1->fresh()->role)->toBe('admin');

    $this->actingAs($admin1, 'admin')
        ->patch(route('admin-users.toggle-status', $admin2))
        ->assertSessionHas('success', 'Akun pengguna berhasil dinonaktifkan.');

    expect($admin2->fresh()->is_active)->toBeFalse();
});

test('an administrator can manage only its own direct administrator child', function () {
    $admin1 = User::factory()->create(['role' => 'admin']);
    $admin2 = User::factory()->create(['role' => 'admin', 'created_by' => $admin1->id]);
    $admin3 = User::factory()->create(['role' => 'admin', 'created_by' => $admin2->id]);

    $this->actingAs($admin2, 'admin')
        ->patch(route('admin-users.toggle-status', $admin3))
        ->assertSessionHas('success', 'Akun pengguna berhasil dinonaktifkan.');

    $this->actingAs($admin3, 'admin')
        ->patch(route('admin-users.toggle-status', $admin2))
        ->assertSessionHas('error', 'Anda tidak memiliki izin untuk mengelola administrator ini.');

    expect($admin2->fresh()->is_active)->toBeTrue()
        ->and($admin3->fresh()->is_active)->toBeFalse();
});

test('an administrator with administrator children cannot be deleted until they are managed', function () {
    $admin1 = User::factory()->create(['role' => 'admin']);
    $admin2 = User::factory()->create(['role' => 'admin', 'created_by' => $admin1->id]);
    User::factory()->create(['role' => 'admin', 'created_by' => $admin2->id]);

    $this->actingAs($admin1, 'admin')
        ->delete(route('admin-users.destroy', $admin2))
        ->assertSessionHas('error', 'Administrator ini masih memiliki administrator turunan. Kelola atau hapus administrator turunannya terlebih dahulu.');

    expect($admin2->fresh())->not->toBeNull();
});

test('the creator can delete a directly created administrator without administrator children', function () {
    $admin1 = User::factory()->create(['role' => 'admin']);
    $admin2 = User::factory()->create(['role' => 'admin', 'created_by' => $admin1->id]);

    $this->actingAs($admin1, 'admin')
        ->delete(route('admin-users.destroy', $admin2))
        ->assertRedirect(route('admin-users.index'));

    expect(User::query()->find($admin2->id))->toBeNull();
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

test('administrators can block and reactivate another user account', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);

    $this->actingAs($admin, 'admin')
        ->patch(route('admin-users.toggle-status', $customer))
        ->assertRedirect(route('admin-users.index'))
        ->assertSessionHas('success', 'Akun pengguna berhasil dinonaktifkan.');

    expect($customer->fresh()->is_active)->toBeFalse();

    $this->patch(route('admin-users.toggle-status', $customer))
        ->assertRedirect(route('admin-users.index'))
        ->assertSessionHas('success', 'Akun pengguna berhasil diaktifkan kembali.');

    expect($customer->fresh()->is_active)->toBeTrue();
});

test('inactive customers and administrators cannot log in', function () {
    $customer = User::factory()->create([
        'email' => 'blocked-customer@example.test',
        'role' => 'customer',
        'is_active' => false,
    ]);
    $admin = User::factory()->create([
        'email' => 'blocked-admin@example.test',
        'role' => 'admin',
        'is_active' => false,
    ]);

    $this->post(route('login'), [
        'email' => $customer->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->post(route('admin.login.store'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');
});

test('administrators cannot deactivate their own account', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

    $this->actingAs($admin, 'admin')
        ->patch(route('admin-users.toggle-status', $admin))
        ->assertSessionHas('error', 'Anda tidak dapat menonaktifkan akun yang sedang digunakan.');

    expect($admin->fresh()->is_active)->toBeTrue();
});

test('administrators can deactivate another administrator without affecting their own account', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $otherAdmin = User::factory()->create(['role' => 'admin', 'is_active' => true, 'created_by' => $admin->id]);

    $this->actingAs($admin, 'admin')
        ->patch(route('admin-users.toggle-status', $otherAdmin))
        ->assertSessionHas('success', 'Akun pengguna berhasil dinonaktifkan.');

    expect($otherAdmin->fresh()->is_active)->toBeFalse()
        ->and($admin->fresh()->is_active)->toBeTrue();
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
