<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        $response = $this->get('/reset-password/'.$notification->token);

        $response->assertStatus(200);

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        return true;
    });
});

test('password reset notification uses the ecommerce email template', function () {
    $user = User::factory()->create(['name' => 'Jane Customer']);
    $token = 'test-reset-token';

    $mail = (new ResetPassword($token))->toMail($user);

    expect($mail->subject)->toBe('Atur Ulang Password - '.config('app.name'))
        ->and($mail->view)->toBe([
            'html' => 'emails.password-reset',
            'text' => 'emails.password-reset-text',
        ])
        ->and($mail->viewData['userName'])->toBe('Jane Customer')
        ->and($mail->viewData['expireMinutes'])->toBe(config('auth.passwords.'.config('auth.defaults.passwords').'.expire'))
        ->and($mail->viewData['resetUrl'])->toContain($token)
        ->and($mail->viewData['resetUrl'])->toContain(url('/reset-password/'));
});

test('customer password reset token cannot reset an account after it becomes an administrator', function () {
    Notification::fake();

    $user = User::factory()->create(['role' => 'customer']);
    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $user->update(['role' => 'admin']);

        $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'ChangedPassword123!',
            'password_confirmation' => 'ChangedPassword123!',
        ])->assertSessionHasErrors('email');

        return true;
    });

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});
