<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Ensure the active request uses an authenticated administrator guard.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('admin');

        if (! $guard->check() || $guard->user()->role !== 'admin' || ! $guard->user()->is_active) {
            $guard->logout();

            return redirect()->route('admin.login');
        }

        Auth::shouldUse('admin');

        return $next($request);
    }
}
