<div class="grid gap-4 sm:grid-cols-2" data-region-form
    data-provinces-url="{{ route('wilayah.provinces') }}"
    data-regencies-url="{{ route('wilayah.regencies') }}"
    data-districts-url="{{ route('wilayah.districts') }}"
    data-villages-url="{{ route('wilayah.villages') }}">
    <div>
        <label for="label" class="mb-1.5 block text-sm font-medium text-gray-700">Label alamat</label>
        <input id="label" name="label" value="{{ old('label', $address->label) }}" placeholder="Rumah, Kantor, Kos" required maxlength="50" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
        @error('label')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="recipient_name" class="mb-1.5 block text-sm font-medium text-gray-700">Nama penerima</label>
        <input id="recipient_name" name="recipient_name" value="{{ old('recipient_name', $address->recipient_name) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
        @error('recipient_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="phone" class="mb-1.5 block text-sm font-medium text-gray-700">Nomor telepon</label>
        <input id="phone" name="phone" type="tel" value="{{ old('phone', $address->phone) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
        @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div class="sm:col-span-2">
        <label for="address_line" class="mb-1.5 block text-sm font-medium text-gray-700">Nama jalan, gedung, nomor rumah, RT/RW</label>
        <textarea id="address_line" name="address_line" rows="3" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">{{ old('address_line', $address->address_line) }}</textarea>
        @error('address_line')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="province_code" class="mb-1.5 block text-sm font-medium text-gray-700">Provinsi</label>
        <select id="province_code" name="province_code" required data-selected="{{ old('province_code', $address->province_code) }}" class="region-select w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"><option value="">Memuat provinsi...</option></select>
        @error('province_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="regency_code" class="mb-1.5 block text-sm font-medium text-gray-700">Kota/Kabupaten</label>
        <select id="regency_code" name="regency_code" required data-selected="{{ old('regency_code', $address->regency_code) }}" disabled class="region-select w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm disabled:bg-gray-100 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"><option value="">Pilih provinsi terlebih dahulu</option></select>
        @error('regency_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="district_code" class="mb-1.5 block text-sm font-medium text-gray-700">Kecamatan</label>
        <select id="district_code" name="district_code" required data-selected="{{ old('district_code', $address->district_code) }}" disabled class="region-select w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm disabled:bg-gray-100 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"><option value="">Pilih kota/kabupaten terlebih dahulu</option></select>
        @error('district_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="village_code" class="mb-1.5 block text-sm font-medium text-gray-700">Kelurahan/Desa</label>
        <select id="village_code" name="village_code" required data-selected="{{ old('village_code', $address->village_code) }}" disabled class="region-select w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm disabled:bg-gray-100 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"><option value="">Pilih kecamatan terlebih dahulu</option></select>
        @error('village_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="postal_code" class="mb-1.5 block text-sm font-medium text-gray-700">Kode pos</label>
        <input id="postal_code" name="postal_code" value="{{ old('postal_code', $address->postal_code) }}" required maxlength="15" inputmode="numeric" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
        <p class="mt-1 text-xs text-gray-500">Terisi otomatis dari kelurahan/desa. Bisa disesuaikan jika data tidak tersedia.</p>
        @error('postal_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="country" class="mb-1.5 block text-sm font-medium text-gray-700">Negara</label>
        <input id="country" name="country" value="Indonesia" readonly required class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm text-gray-600">
        @error('country')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <p class="sm:col-span-2 text-xs text-gray-500" data-region-status aria-live="polite">Pilih provinsi untuk memulai.</p>
</div>
<label class="mt-5 flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="is_default" value="1" @checked(old('is_default', $address->is_default)) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">Jadikan alamat utama</label>
<div class="mt-6 flex items-center justify-end gap-3"><a href="{{ route('addresses.index') }}" class="rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-700">Batal</a><button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Simpan alamat</button></div>
