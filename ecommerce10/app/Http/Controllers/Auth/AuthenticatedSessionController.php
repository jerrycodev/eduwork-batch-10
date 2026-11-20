<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\CustomerActivityLog;
use App\Support\CustomerDeviceInfo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

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

        $customer = Auth::guard('customer')->user();
        if ($customer && $customer->role === 'customer') {
            try {
                CustomerActivityLog::query()->create([
                    'user_id' => $customer->id,
                    'actor_name' => $customer->name,
                    'actor_email' => $customer->email,
                    'action' => 'customer.login',
                    'route_name' => 'login',
                    'method' => 'POST',
                    'response_status' => 302,
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 2000) ?: null,
                    'metadata' => array_merge(['path' => '/login', 'response_status' => 302], CustomerDeviceInfo::fromRequest($request)),
                    'created_at' => now(),
                ]);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        $redirectTo = $this->safeRedirectTo($request->input('redirect_to'));

        // The intended URL is shared by Laravel's session, but customer and
        // admin authentication must never redirect into each other's workspace.
        $intended = $request->session()->pull('url.intended');
        $safeIntended = $this->safeRedirectTo($intended);

        return redirect()->to($redirectTo ?? $safeIntended ?? route('account', absolute: false));
    }

    /**
     * Accept only a local path to prevent open-redirect vulnerabilities.
     */
    private function safeRedirectTo(mixed $destination): ?string
    {
        if (! is_string($destination) || $destination === '' || str_contains($destination, '\\') || preg_match('/[\x00-\x1F\x7F]/', $destination)) {
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

        if ($path === '/admin' || str_starts_with($path, '/admin/') || $path === '/dashboard' || str_starts_with($path, '/dashboard/')) {
            return null;
        }

        return $destination;
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();

        $request->session()->regenerate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
