<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        $redirectTo = $this->safeRedirectTo($request->input('redirect_to'));

        return $redirectTo
            ? redirect()->to($redirectTo)
            : redirect(route('dashboard', absolute: false));
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
}
