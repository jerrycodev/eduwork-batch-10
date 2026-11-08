<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $redirectTo = $this->safeRedirectTo($request->input('redirect_to'));

        return $redirectTo
            ? redirect()->to($redirectTo)
            : redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Accept only a local path to prevent open-redirect vulnerabilities.
     */
    private function safeRedirectTo(mixed $destination): ?string
    {
        if (! is_string($destination) || $destination === '' || str_contains($destination, "\\") || preg_match('/[\x00-\x1F\x7F]/', $destination)) {
            return null;
        }

        $parts = parse_url($destination);

        if ($parts === false || isset($parts['scheme']) || isset($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $path = $parts['path'] ?? '';

        if (! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return null;
        }

        return $destination;
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
