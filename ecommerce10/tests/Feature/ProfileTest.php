<?php

use App\Models\EmailDeliveryLog;
use App\Models\User;
use App\Notifications\ConfirmEmailChange;
use App\Notifications\EmailAddressChanged;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;


test('email change notifications render for anonymous mail recipients', function () {
    $recipient = new AnonymousNotifiable;

    $confirmation = (new ConfirmEmailChange('https://example.test/confirm', 'log-confirm', 'Jane'))
        ->toMail($recipient);
    $changed = (new EmailAddressChanged('new@example.test', 'log-changed', 'Jane'))
        ->toMail($recipient);

    expect($confirmation->introLines)->toContain('Kami menerima permintaan untuk mengubah alamat email akun RevStore Anda.')
        ->and($changed->introLines)->toContain('Alamat email akun RevStore Anda telah berhasil diubah menjadi new@example.test.');
});

test('customer profile page uses the customer storefront layout and customer settings', function () {
    $user = User::factory()->create(['role' => 'customer']);

    $this->actingAs($user, 'customer')
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Profil Saya')
        ->assertSee('Alamat Pengiriman')
        ->assertSee('id="name"', false)
        ->assertSee('id="email"', false)
        ->assertSee('type="text"', false)
        ->assertSee('type="email"', false)
        ->assertSee('value="'.$user->name.'"', false)
        ->assertSee('value="'.$user->email.'"', false)
        ->assertSee('name="password"', false)
        ->assertSee('Password saat ini', false)
        ->assertSee('<button type="submit"', false)
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

test('customer can request an email change but old email remains active until confirmation', function () {
    Notification::fake();
    $user = User::factory()->create(['role' => 'customer']);
    $oldEmail = $user->email;

    $this->actingAs($user, 'customer')
        ->patch(route('profile.update'), [
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'password' => 'password',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('status', 'email-change-pending');

    expect($user->fresh()->name)->toBe('Test Customer')
        ->and($user->fresh()->email)->toBe($oldEmail)
        ->and($user->fresh()->pending_email)->toBe('customer@example.com')
        ->and($user->fresh()->email_verified_at)->not->toBeNull();

    Notification::assertSentOnDemand(ConfirmEmailChange::class);
    $confirmationLog = EmailDeliveryLog::query()->where('type', 'email_change_confirmation')->sole();
    expect($confirmationLog->recipient)->toBe('customer@example.com')
        ->and($confirmationLog->order_id)->toBeNull()
        ->and($confirmationLog->status)->toBe('queued');

    $this->actingAs($user->fresh(), 'customer')->get(route('profile.edit'))
        ->assertSee('customer@example.com')
        ->assertSee('Menunggu konfirmasi email baru');
});

test('customer can resend pending email change confirmation and it is recorded in delivery logs', function () {
    Notification::fake();
    $user = User::factory()->create(['role' => 'customer']);
    $user->forceFill(['pending_email' => 'new-address@example.com'])->save();

    $this->actingAs($user, 'customer')
        ->post(route('profile.email-change.resend'))
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('status', 'email-change-resent');

    Notification::assertSentOnDemand(ConfirmEmailChange::class);
    $log = EmailDeliveryLog::query()->where('type', 'email_change_confirmation')->sole();

    expect($log->recipient)->toBe('new-address@example.com')
        ->and($log->status)->toBe('queued')
        ->and($log->order_id)->toBeNull();

    $this->actingAs($user->fresh(), 'customer')->get(route('profile.edit'))
        ->assertSee('Kirim ulang email konfirmasi')
        ->assertSee('form="resend-email-change-form"', false)
        ->assertSee('id="resend-email-change-form"', false)
        ->assertSee('Email konfirmasi baru berhasil dikirim.');
});

test('customer cannot resend email confirmation without a pending email change', function () {
    $user = User::factory()->create(['role' => 'customer']);

    $this->actingAs($user, 'customer')
        ->post(route('profile.email-change.resend'))
        ->assertNotFound();

    expect(EmailDeliveryLog::query()->count())->toBe(0);
});

test('customer must provide current password to request an email change', function () {
    $user = User::factory()->create(['role' => 'customer']);

    $this->actingAs($user, 'customer')
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => 'Changed Name',
            'email' => 'customer@example.com',
        ])
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh()->email)->not->toBe('customer@example.com')
        ->and($user->fresh()->name)->toBe($user->name)
        ->and($user->fresh()->pending_email)->toBeNull();
});

test('signed email change link activates new email and invalidates pending request', function () {
    Notification::fake();
    $user = User::factory()->create(['role' => 'customer']);
    $user->forceFill(['pending_email' => 'customer@example.com'])->save();
    $url = URL::temporarySignedRoute('profile.email-change.confirm', now()->addMinutes(60), [
        'user' => $user->id,
        'hash' => hash('sha256', 'customer@example.com'),
    ]);

    $this->get($url)
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'email-change-confirmed');

    expect($user->fresh()->email)->toBe('customer@example.com')
        ->and($user->fresh()->pending_email)->toBeNull()
        ->and($user->fresh()->email_verified_at)->not->toBeNull();

    Notification::assertSentOnDemand(EmailAddressChanged::class);
    $changeLog = EmailDeliveryLog::query()->where('type', 'email_changed_notification')->sole();
    expect($changeLog->recipient)->toBe($user->getRawOriginal('email'))
        ->and($changeLog->order_id)->toBeNull()
        ->and($changeLog->status)->toBe('queued');

    $this->get($url)->assertNotFound();
});

test('expired or tampered email change link is rejected', function () {
    $user = User::factory()->create(['role' => 'customer']);
    $user->forceFill(['pending_email' => 'customer@example.com'])->save();
    $expiredUrl = URL::temporarySignedRoute('profile.email-change.confirm', now()->subMinute(), [
        'user' => $user->id,
        'hash' => hash('sha256', 'customer@example.com'),
    ]);

    $this->get($expiredUrl)->assertForbidden();
    expect($user->fresh()->email)->not->toBe('customer@example.com');
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
