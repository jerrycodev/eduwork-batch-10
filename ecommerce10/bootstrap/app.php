<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CustomerMiddleware;
use App\Http\Middleware\CustomerOrAdminMiddleware;
use App\Http\Middleware\UseCustomerGuard;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', UseCustomerGuard::class);

        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('dashboard') || $request->is('dashboard/*') || $request->is('admin/*') ? route('admin.login') : route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => $request->is('admin/*') || $request->is('dashboard') || $request->is('dashboard/*')
            ? route('dashboard')
            : route('home'));

        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'customer' => CustomerMiddleware::class,
            'customer-or-admin' => CustomerOrAdminMiddleware::class,
        ]);

        // Midtrans sends server-to-server POST requests without a Laravel CSRF token.
        $middleware->validateCsrfTokens(except: [
            'midtrans/callback',
            'webhooks/biteship',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
