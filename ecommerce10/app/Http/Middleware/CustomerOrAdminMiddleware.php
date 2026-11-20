<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CustomerOrAdminMiddleware
{
    /**
     * Select the authenticated account guard for shared, owner-scoped resources.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        foreach (['admin', 'customer'] as $guardName) {
            $guard = Auth::guard($guardName);
            $expectedRole = $guardName === 'admin' ? 'admin' : 'customer';

            if ($guard->check() && $guard->user()->role === $expectedRole) {
                Auth::shouldUse($guardName);

                return $next($request);
            }
        }

        return redirect()->route('login');
    }
}
