<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CustomerMiddleware
{
    /**
     * Ensure the customer guard only carries customer accounts.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('customer');

        if (! $guard->check() || $guard->user()->role !== 'customer') {
            $guard->logout();

            return redirect()->route('login');
        }

        Auth::shouldUse('customer');

        return $next($request);
    }
}
