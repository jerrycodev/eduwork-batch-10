<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserAddressRequest;
use App\Models\UserAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserAddressController extends Controller
{
    /**
     * Menampilkan daftar data dengan filter dan relasi yang diperlukan untuk kebutuhan operasional.
     */
    public function index(Request $request): View
    {
        $addresses = $request->user()->addresses()->orderByDesc('is_default')->orderByDesc('updated_at')->get();

        return view('addresses.index', compact('addresses'));
    }

    /**
     * Menyiapkan halaman pembuatan data baru.
     */
    public function create(): View
    {
        return view('addresses.create', ['address' => new UserAddress]);
    }

    /**
     * Memvalidasi dan memproses permintaan pembuatan data baru sesuai aturan bisnis.
     */
    public function store(UserAddressRequest $request): RedirectResponse
    {
        $validated = $request->validatedAddress();
        $user = $request->user();

        DB::transaction(function () use ($user, $validated): void {
            $makeDefault = (bool) ($validated['is_default'] ?? false) || ! $user->addresses()->exists();
            if ($makeDefault) {
                $user->addresses()->update(['is_default' => false]);
            }

            $user->addresses()->create([...$validated, 'is_default' => $makeDefault]);
        });

        return redirect()->route('addresses.index')->with('success', 'Alamat berhasil ditambahkan.');
    }

    /**
     * Menyiapkan halaman perubahan data yang sudah ada.
     */
    public function edit(Request $request, UserAddress $address): View
    {
        $this->ensureOwner($request, $address);

        return view('addresses.edit', compact('address'));
    }

    /**
     * Memvalidasi dan menyimpan perubahan data dengan tetap menjaga aturan bisnis.
     */
    public function update(UserAddressRequest $request, UserAddress $address): RedirectResponse
    {
        $this->ensureOwner($request, $address);
        $validated = $request->validatedAddress();
        $user = $request->user();

        DB::transaction(function () use ($address, $user, $validated): void {
            $makeDefault = (bool) ($validated['is_default'] ?? false) || $address->is_default;
            if ($makeDefault) {
                $user->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
            }

            $address->update([...$validated, 'is_default' => $makeDefault]);
        });

        return redirect()->route('addresses.index')->with('success', 'Alamat berhasil diperbarui.');
    }

    /**
     * Memeriksa ketentuan penghapusan lalu menghapus data yang diizinkan.
     */
    public function destroy(Request $request, UserAddress $address): RedirectResponse
    {
        $this->ensureOwner($request, $address);
        $wasDefault = $address->is_default;
        $user = $request->user();

        DB::transaction(function () use ($address, $user, $wasDefault): void {
            $address->delete();
            if ($wasDefault) {
                $nextAddress = $user->addresses()->oldest()->first();
                $nextAddress?->update(['is_default' => true]);
            }
        });

        return redirect()->route('addresses.index')->with('success', 'Alamat berhasil dihapus.');
    }

    public function makeDefault(Request $request, UserAddress $address): RedirectResponse
    {
        $this->ensureOwner($request, $address);
        $user = $request->user();

        DB::transaction(function () use ($user, $address): void {
            $user->addresses()->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });

        return redirect()->route('addresses.index')->with('success', 'Alamat utama berhasil diubah.');
    }

    private function ensureOwner(Request $request, UserAddress $address): void
    {
        abort_unless((string) $address->user_id === (string) $request->user()->id, 404);
    }
}
