<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Menyiapkan halaman perubahan data yang sudah ada.
     */
    public function edit(Request $request): View|RedirectResponse
    {
        if ($request->user()->role === 'admin') {
            return redirect()->route('admin.profile.edit');
        }

        return view('profile.customer.edit', [
            'user' => $request->user(),
            'profileUpdateRoute' => 'profile.update',
            'profileDestroyRoute' => 'profile.destroy',
        ]);
    }

    public function adminEdit(Request $request): View
    {
        return view('profile.admin.edit', [
            'user' => $request->user(),
            'profileUpdateRoute' => 'admin.profile.update',
            'profileDestroyRoute' => 'admin.profile.destroy',
        ]);
    }

    /**
     * Memvalidasi dan menyimpan perubahan data dengan tetap menjaga aturan bisnis.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $route = $user->role === 'admin' ? 'admin.profile.edit' : 'profile.edit';

        return Redirect::route($route)->with('status', 'profile-updated');
    }

    /**
     * Memeriksa ketentuan penghapusan lalu menghapus data yang diizinkan.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($user->role === 'admin' && User::query()->where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Administrator terakhir tidak dapat menghapus akunnya. Tambahkan administrator lain terlebih dahulu.');
        }

        Auth::guard($user->role === 'admin' ? 'admin' : 'customer')->logout();
        $user->delete();

        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
