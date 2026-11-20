<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    /**
     * Menyiapkan halaman pembuatan data baru.
     */
    public function create(): View
    {
        return view('admin.auth.login');
    }

    /**
     * Memvalidasi dan memproses permintaan pembuatan data baru sesuai aturan bisnis.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate('admin', 'admin');
        $request->session()->regenerate();
        Auth::shouldUse('admin');

        // Ignore customer/storefront intended URLs left in the shared session.
        $intended = $request->session()->pull('url.intended');
        $path = is_string($intended) ? parse_url($intended, PHP_URL_PATH) : null;
        $isAdminDestination = is_string($path)
            && ($path === '/dashboard' || str_starts_with($path, '/dashboard/'));

        return redirect()->to($isAdminDestination ? $intended : route('dashboard', absolute: false));
    }

    /**
     * Memeriksa ketentuan penghapusan lalu menghapus data yang diizinkan.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
