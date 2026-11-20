<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminNewPasswordController extends Controller
{
    /**
     * Menyiapkan halaman pembuatan data baru.
     */
    public function create(Request $request): View
    {
        return view('admin.auth.reset-password', ['request' => $request]);
    }

    /**
     * Memvalidasi dan memproses permintaan pembuatan data baru sesuai aturan bisnis.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $isAdmin = User::query()->where('email', $request->email)->where('role', 'admin')->exists();
        $status = $isAdmin ? Password::broker('admins')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request): void {
                if ($user->role !== 'admin') {
                    throw ValidationException::withMessages(['email' => __(Password::INVALID_USER)]);
                }

                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        ) : Password::INVALID_USER;

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('admin.login')->with('status', __($status))
            : back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }
}
