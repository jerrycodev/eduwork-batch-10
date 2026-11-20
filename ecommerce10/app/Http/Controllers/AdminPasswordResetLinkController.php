<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class AdminPasswordResetLinkController extends Controller
{
    /**
     * Menyiapkan halaman pembuatan data baru.
     */
    public function create(): View
    {
        return view('admin.auth.forgot-password');
    }

    /**
     * Memvalidasi dan memproses permintaan pembuatan data baru sesuai aturan bisnis.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = User::query()->where('email', $request->email)->where('role', 'admin')->exists()
            ? Password::broker('admins')->sendResetLink($request->only('email'))
            : Password::RESET_LINK_SENT;

        return back()->with('status', __($status));
    }
}
