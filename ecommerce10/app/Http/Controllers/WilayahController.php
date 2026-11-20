<?php

namespace App\Http\Controllers;

use Aliziodev\Wilayah\Models\District;
use Aliziodev\Wilayah\Models\Province;
use Aliziodev\Wilayah\Models\Regency;
use Aliziodev\Wilayah\Models\Village;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WilayahController extends Controller
{
    /**
     * Menyediakan daftar provinsi untuk pilihan alamat.
     */
    public function provinces(): JsonResponse
    {
        return response()->json(Province::query()->orderBy('name')->get(['code', 'name'])->map(
            fn (Province $province) => ['value' => $province->code, 'label' => $province->name]
        ));
    }

    /**
     * Menyediakan daftar kabupaten/kota berdasarkan provinsi yang dipilih.
     */
    public function regencies(Request $request): JsonResponse
    {
        $validated = $request->validate(['province' => ['required', 'string', 'exists:provinces,code']]);
        $province = Province::query()->where('code', $validated['province'])->firstOrFail();

        return response()->json(Regency::query()->where('province_id', $province->id)->orderBy('name')->get(['code', 'name'])->map(
            fn (Regency $regency) => ['value' => $regency->code, 'label' => $regency->name]
        ));
    }

    /**
     * Menyediakan daftar kecamatan berdasarkan kabupaten/kota yang dipilih.
     */
    public function districts(Request $request): JsonResponse
    {
        $validated = $request->validate(['regency' => ['required', 'string', 'exists:regencies,code']]);
        $regency = Regency::query()->where('code', $validated['regency'])->firstOrFail();

        return response()->json(District::query()->where('regency_id', $regency->id)->orderBy('name')->get(['code', 'name'])->map(
            fn (District $district) => ['value' => $district->code, 'label' => $district->name]
        ));
    }

    /**
     * Menyediakan daftar desa/kelurahan berdasarkan kecamatan yang dipilih.
     */
    public function villages(Request $request): JsonResponse
    {
        $validated = $request->validate(['district' => ['required', 'string', 'exists:districts,code']]);
        $district = District::query()->where('code', $validated['district'])->firstOrFail();

        return response()->json(Village::query()->where('district_id', $district->id)->orderBy('name')->get(['code', 'name', 'postal_code', 'type'])->map(
            fn (Village $village) => [
                'value' => $village->code,
                'label' => $village->name.($village->type === 1 ? ' (Kelurahan)' : ' (Desa)'),
                'postal_code' => $village->postal_code,
            ]
        ));
    }
}
