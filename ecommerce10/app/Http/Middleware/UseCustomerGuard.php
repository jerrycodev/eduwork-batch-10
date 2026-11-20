<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UseCustomerGuard
{
    /**
     * Reset the default guard for each web request before route middleware runs.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guard = $request->is('broadcasting/auth') && Auth::guard('admin')->check()
            ? 'admin'
            : 'customer';

        Auth::shouldUse($guard);

        return $next($request);
    }
}
