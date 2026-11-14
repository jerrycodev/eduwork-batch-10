<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::toMailUsing(function ($notifiable, string $token): MailMessage {
            $resetUrl = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
            $appName = config('app.name');
            $viewData = [
                'appName' => $appName,
                'userName' => $notifiable->name ?: 'Pelanggan',
                'resetUrl' => $resetUrl,
                'expireMinutes' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
            ];

            return (new MailMessage)
                ->subject('Atur Ulang Password - '.$appName)
                ->view('emails.password-reset', $viewData)
                ->text('emails.password-reset-text', $viewData);
        });

        Blade::if('admin', function () {
            // Logika pengecekan: pastikan user login DAN memiliki role/guard admin
            return Auth::check() && Auth::user()->role === 'admin';
        });

        // Unlessadmin
        Blade::if('unlessadmin', function () {
            // Logika pengecekan: pastikan user login DAN memiliki role/guard admin
            return ! Auth::check() || Auth::user()->role !== 'admin';
        });
    }
}
