<?php

namespace App\Http\Controllers;

use Aliziodev\Wilayah\Models\District;
use Aliziodev\Wilayah\Models\Province;
use Aliziodev\Wilayah\Models\Regency;
use Aliziodev\Wilayah\Models\Village;
use App\Models\UserAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserAddressController extends Controller
{
    public function index(Request $request): View
    {
        $addresses = $request->user()->addresses()->orderByDesc('is_default')->orderByDesc('updated_at')->get();

        return view('addresses.index', compact('addresses'));
    }

    public function create(): View
    {
        return view('addresses.create', ['address' => new UserAddress]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateAddress($request);
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

    public function edit(Request $request, UserAddress $address): View
    {
        $this->ensureOwner($request, $address);

        return view('addresses.edit', compact('address'));
    }

    public function update(Request $request, UserAddress $address): RedirectResponse
    {
        $this->ensureOwner($request, $address);
        $validated = $this->validateAddress($request);
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

    private function validateAddress(Request $request): array
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address_line' => ['required', 'string', 'max:2000'],
            'province_code' => ['required', 'string', 'size:2'],
            'regency_code' => ['required', 'string', 'size:5'],
            'district_code' => ['required', 'string', 'size:8'],
            'village_code' => ['required', 'string', 'size:13'],
            'postal_code' => ['required', 'string', 'max:15'],
            'country' => ['required', 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
        ], [
            'required' => ':attribute wajib diisi.',
        ]);

        $province = Province::query()->where('code', $validated['province_code'])->first();
        $regency = $province
            ? Regency::query()->where('code', $validated['regency_code'])->where('province_id', $province->id)->first()
            : null;
        $district = $regency
            ? District::query()->where('code', $validated['district_code'])->where('regency_id', $regency->id)->first()
            : null;
        $village = $district
            ? Village::query()->where('code', $validated['village_code'])->where('district_id', $district->id)->first()
            : null;

        if (! $province || ! $regency || ! $district || ! $village) {
            throw ValidationException::withMessages([
                'village_code' => 'Kombinasi provinsi, kota, kecamatan, dan kelurahan tidak valid.',
            ]);
        }

        return [...$validated,
            'province' => $province->name,
            'city' => $regency->name,
            'district' => $district->name,
            'village' => $village->name,
            'postal_code' => $village->postal_code ?: $validated['postal_code'],
        ];
    }

    private function ensureOwner(Request $request, UserAddress $address): void
    {
        abort_unless((string) $address->user_id === (string) $request->user()->id, 404);
    }
}
