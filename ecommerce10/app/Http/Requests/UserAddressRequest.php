<?php

namespace App\Http\Requests;

use Aliziodev\Wilayah\Models\District;
use Aliziodev\Wilayah\Models\Province;
use Aliziodev\Wilayah\Models\Regency;
use Aliziodev\Wilayah\Models\Village;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UserAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        $address = $this->route('address');
        if ($address) {
            abort_unless((string) $address->user_id === (string) $this->user()?->id, 404);
        }

        return true;
    }

    public function rules(): array
    {
        return [
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
        ];
    }

    public function messages(): array
    {
        return ['required' => ':attribute wajib diisi.'];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $codes = $this->only('province_code', 'regency_code', 'district_code', 'village_code');
            if (count(array_filter($codes, fn ($value) => filled($value))) !== 4) {
                return;
            }

            $province = Province::query()->where('code', $codes['province_code'])->first();
            $regency = $province
                ? Regency::query()->where('code', $codes['regency_code'])->where('province_id', $province->id)->first()
                : null;
            $district = $regency
                ? District::query()->where('code', $codes['district_code'])->where('regency_id', $regency->id)->first()
                : null;
            $village = $district
                ? Village::query()->where('code', $codes['village_code'])->where('district_id', $district->id)->first()
                : null;

            if (! $province || ! $regency || ! $district || ! $village) {
                $validator->errors()->add('village_code', 'Kombinasi provinsi, kota, kecamatan, dan kelurahan tidak valid.');
            }
        }];
    }

    public function validatedAddress(): array
    {
        $validated = $this->validated();
        $province = Province::query()->where('code', $validated['province_code'])->firstOrFail();
        $regency = Regency::query()->where('code', $validated['regency_code'])->where('province_id', $province->id)->firstOrFail();
        $district = District::query()->where('code', $validated['district_code'])->where('regency_id', $regency->id)->firstOrFail();
        $village = Village::query()->where('code', $validated['village_code'])->where('district_id', $district->id)->firstOrFail();

        return [...$validated,
            'province' => $province->name,
            'city' => $regency->name,
            'district' => $district->name,
            'village' => $village->name,
            'postal_code' => $village->postal_code ?: $validated['postal_code'],
        ];
    }
}
