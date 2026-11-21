{{-- Dokumentasi: Form akun admin/pelanggan untuk pembuatan dan perubahan data pengguna. --}}
<form method="POST" action="{{ $user->exists ? route('admin-users.update', $user) : route('admin-users.store') }}"
    onsubmit="this.querySelector('#role-value').value = this.querySelector('#role').value"
    class="space-y-5">
    @csrf
    @if ($user->exists)
        @method('PUT')
    @endif

    <div>
        <label for="name" class="mb-1.5 block text-sm font-semibold text-slate-700">Nama lengkap</label>
        <input id="name" name="name" type="text" required maxlength="255"
            value="{{ old('name', $user->name) }}"
            class="w-full rounded-xl border-slate-200 text-sm focus:border-teal-500 focus:ring-teal-500">
        @error('name')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">Email</label>
        <input id="email" name="email" type="email" required maxlength="255"
            value="{{ old('email', $user->email) }}"
            class="w-full rounded-xl border-slate-200 text-sm focus:border-teal-500 focus:ring-teal-500">
        @error('email')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
        @if ($user->exists)
            <p class="mt-1 text-xs text-slate-500">Mengubah email akan mengatur ulang status verifikasi email.</p>
        @endif
    </div>

    <div>
        <label for="role" class="mb-1.5 block text-sm font-semibold text-slate-700">Role pengguna</label>
        <input id="role-value" type="hidden" name="role" value="{{ old('role', $user->role ?: 'customer') }}">
        <select id="role" required
            onchange="document.getElementById('role-value').value = this.value; document.getElementById('role-validation-error')?.remove()"
            class="w-full rounded-xl border-slate-200 text-sm focus:border-teal-500 focus:ring-teal-500">
            @foreach ($roles as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $user->role ?: 'customer') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('role')
            <p id="role-validation-error" class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
        <p class="mt-1 text-xs text-slate-500">Pilih Pelanggan atau Administrator sesuai hak akses yang dibutuhkan.</p>
    </div>

    <div class="border-t border-slate-100 pt-5">
        <h3 class="text-sm font-semibold text-slate-800">
            {{ $user->exists ? 'Ubah password (opsional)' : 'Password awal' }}
        </h3>
        <p class="mt-1 text-xs text-slate-500">
            {{ $user->exists ? 'Kosongkan jika tidak ingin mengubah password.' : 'Gunakan password yang kuat dan sampaikan secara aman kepada pengguna.' }}
        </p>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-700">
                    Password {{ $user->exists ? '(opsional)' : '(wajib diisi)' }}
                </label>
                <input id="password" name="password" type="password"
                    @required(!$user->exists) autocomplete="new-password"
                    class="w-full rounded-xl border-slate-200 text-sm focus:border-teal-500 focus:ring-teal-500">
                @if (!$user->exists)
                    <p class="mt-1 text-xs text-slate-500">Gunakan minimal 8 karakter.</p>
                @endif
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-slate-700">
                    Konfirmasi password {{ $user->exists ? '(jika mengubah password)' : '(wajib diisi)' }}
                </label>
                <input id="password_confirmation" name="password_confirmation" type="password"
                    @required(!$user->exists) autocomplete="new-password"
                    class="w-full rounded-xl border-slate-200 text-sm focus:border-teal-500 focus:ring-teal-500">
            </div>
        </div>
    </div>

    @unless ($user->exists)
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <label for="email_verified" class="flex cursor-pointer items-start gap-3">
                <input id="email_verified" name="email_verified" type="checkbox" value="1"
                    @checked(old('email_verified'))
                    class="mt-0.5 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                <span>
                    <span class="block text-sm font-semibold text-slate-800">Tandai email sebagai terverifikasi</span>
                    <span class="mt-1 block text-xs text-slate-500">Pengguna dapat langsung menggunakan fitur yang memerlukan verifikasi email tanpa melakukan verifikasi melalui tautan email.</span>
                </span>
            </label>
        </div>
    @endunless

    <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
        <a href="{{ route('admin-users.index') }}"
            class="rounded-xl border border-slate-200 px-4 py-2.5 text-center text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Batal</a>
        <button type="submit"
            class="rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700">
            {{ $user->exists ? 'Simpan perubahan' : 'Buat pengguna' }}
        </button>
    </div>
</form>
