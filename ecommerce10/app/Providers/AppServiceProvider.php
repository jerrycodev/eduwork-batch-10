<?php

namespace App\Providers;

use App\Services\EmailDeliveryTracker;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
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
        Event::listen(MessageSending::class, [EmailDeliveryTracker::class, 'sending']);
        Event::listen(MessageSent::class, [EmailDeliveryTracker::class, 'sent']);

        ResetPassword::toMailUsing(function ($notifiable, string $token): MailMessage {
            $isAdmin = $notifiable->role === 'admin';
            $resetRoute = $isAdmin ? 'admin.password.reset' : 'password.reset';
            $resetUrl = url(route($resetRoute, [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
            $appName = config('app.name');
            $viewData = [
                'appName' => $appName,
                'userName' => $notifiable->name ?: 'Pelanggan',
                'resetUrl' => $resetUrl,
                'expireMinutes' => config('auth.passwords.'.($isAdmin ? 'admins' : 'customers').'.expire'),
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
