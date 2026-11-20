<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectAuthenticatedFromGuestPages
{
    /**
     * Menjalankan alur bisnis untuk menangani event atau permintaan yang diterima.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isAdminLoginPage = $request->is('admin/*');

        if ($isAdminLoginPage) {
            if (Auth::guard('admin')->check()) {
                return redirect()->route('dashboard');
            }

            if (Auth::guard('customer')->check()) {
                return redirect()->route('home');
            }

            return $next($request);
        }

        if (Auth::guard('customer')->check()) {
            return redirect()->route('home');
        }

        if (Auth::guard('admin')->check()) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
