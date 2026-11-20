<div class="grid gap-5 sm:grid-cols-2" x-data="addressRegionForm({ provinces: @js(route('wilayah.provinces')), regencies: @js(route('wilayah.regencies')), districts: @js(route('wilayah.districts')), villages: @js(route('wilayah.villages')) }, [@js(old('province_code', $address->province_code)), @js(old('regency_code', $address->regency_code)), @js(old('district_code', $address->district_code)), @js(old('village_code', $address->village_code))])">

    <div>
        <label for="label" class="mb-1.5 block text-sm font-medium text-gray-700">Label alamat</label>
        <input id="label" name="label" x-model="form.label" @change="form.validate('label')" value="{{ old('label', $address->label) }}" placeholder="Rumah, Kantor, Kos" required maxlength="50" class="ui-input w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10">
        <x-ui.input-error field="label" :messages="$errors->get('label')" />
        @error('label')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="recipient_name" class="mb-1.5 block text-sm font-medium text-gray-700">Nama penerima</label>
        <input id="recipient_name" name="recipient_name" x-model="form.recipient_name" @change="form.validate('recipient_name')" value="{{ old('recipient_name', $address->recipient_name) }}" required class="ui-input w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10">
        <x-ui.input-error field="recipient_name" :messages="$errors->get('recipient_name')" />
        @error('recipient_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="phone" class="mb-1.5 block text-sm font-medium text-gray-700">Nomor telepon</label>
        <input id="phone" name="phone" x-model="form.phone" @change="form.validate('phone')" type="tel" value="{{ old('phone', $address->phone) }}" required class="ui-input w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10">
        <x-ui.input-error field="phone" :messages="$errors->get('phone')" />
        @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div class="sm:col-span-2">
        <label for="address_line" class="mb-1.5 block text-sm font-medium text-gray-700">Nama jalan, gedung, nomor rumah, RT/RW</label>
        <textarea id="address_line" name="address_line" x-model="form.address_line" @change="form.validate('address_line')" rows="3" required class="ui-input w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10">{{ old('address_line', $address->address_line) }}</textarea>
        <x-ui.input-error field="address_line" :messages="$errors->get('address_line')" />
        @error('address_line')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="province_code" class="mb-1.5 block text-sm font-medium text-gray-700">Provinsi</label>
        <select id="province_code" x-ref="province" name="province_code" x-model="form.province_code" @change="provinceChanged(); form.validate('province_code')" required  class="region-select ui-input w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10"><option value="">Memuat provinsi...</option></select>
        <x-ui.input-error field="province_code" :messages="$errors->get('province_code')" />
        @error('province_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="regency_code" class="mb-1.5 block text-sm font-medium text-gray-700">Kota/Kabupaten</label>
        <select id="regency_code" x-ref="regency" name="regency_code" x-model="form.regency_code" @change="regencyChanged(); form.validate('regency_code')" required  disabled class="region-select ui-input w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm disabled:bg-gray-100 focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10"><option value="">Pilih provinsi terlebih dahulu</option></select>
        <x-ui.input-error field="regency_code" :messages="$errors->get('regency_code')" />
        @error('regency_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="district_code" class="mb-1.5 block text-sm font-medium text-gray-700">Kecamatan</label>
        <select id="district_code" x-ref="district" name="district_code" x-model="form.district_code" @change="districtChanged(); form.validate('district_code')" required  disabled class="region-select ui-input w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm disabled:bg-gray-100 focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10"><option value="">Pilih kota/kabupaten terlebih dahulu</option></select>
        <x-ui.input-error field="district_code" :messages="$errors->get('district_code')" />
        @error('district_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="village_code" class="mb-1.5 block text-sm font-medium text-gray-700">Kelurahan/Desa</label>
        <select id="village_code" x-ref="village" name="village_code" x-model="form.village_code" @change="villageChanged(); form.validate('village_code')" required  disabled class="region-select ui-input w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm disabled:bg-gray-100 focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10"><option value="">Pilih kecamatan terlebih dahulu</option></select>
        <x-ui.input-error field="village_code" :messages="$errors->get('village_code')" />
        @error('village_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="postal_code" class="mb-1.5 block text-sm font-medium text-gray-700">Kode pos</label>
        <input id="postal_code" x-ref="postalCode" name="postal_code" x-model="form.postal_code" @change="form.validate('postal_code')" value="{{ old('postal_code', $address->postal_code) }}" required maxlength="15" inputmode="numeric" class="ui-input w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10">
        <p class="mt-1 text-xs text-gray-500">Terisi otomatis dari kelurahan/desa. Bisa disesuaikan jika data tidak tersedia.</p>
        <x-ui.input-error field="postal_code" :messages="$errors->get('postal_code')" />
        @error('postal_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="country" class="mb-1.5 block text-sm font-medium text-gray-700">Negara</label>
        <input id="country" name="country" x-model="form.country" @change="form.validate('country')" value="Indonesia" readonly required class="ui-input w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-600">
        <x-ui.input-error field="country" :messages="$errors->get('country')" />
        @error('country')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <p class="sm:col-span-2 text-xs text-gray-500" data-region-status aria-live="polite" x-text="status">Pilih provinsi untuk memulai.</p>
</div>
<label class="mt-5 flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="is_default" value="1" x-model="form.is_default" @change="form.validate('is_default')" @checked(old('is_default', $address->is_default)) class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">Jadikan alamat utama</label>
<div class="mt-7 flex flex-col-reverse items-stretch justify-end gap-2 border-t border-gray-100 pt-5 sm:flex-row sm:items-center"><x-ui.link-button :href="route('addresses.index')" variant="outline">Batal</x-ui.link-button><x-ui.button type="submit">Simpan alamat</x-ui.button></div>
