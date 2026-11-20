{{-- Dokumentasi: Form permintaan tautan reset password administrator. --}}
<x-guest-layout>
    <div class="mb-5"><p class="text-xs font-semibold uppercase tracking-wider text-teal-600">Admin workspace</p><h1 class="mt-1 text-xl font-bold text-slate-900">Reset password administrator</h1><p class="mt-2 text-sm leading-6 text-slate-600">Masukkan email administrator. Jika akun ditemukan, tautan reset akan dikirim ke email tersebut.</p></div>
    <x-auth-session-status class="mb-4" :status="session('status')" />
    <form method="POST" action="{{ route('admin.password.email') }}" class="space-y-4">
        @csrf
        <div><x-input-label for="email" :value="__('Email administrator')" /><x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" /><x-input-error :messages="$errors->get('email')" class="mt-2" /></div>
        <x-primary-button class="w-full justify-center">Kirim tautan reset</x-primary-button>
    </form>
    <p class="mt-5 text-center text-sm"><a href="{{ route('admin.login') }}" class="font-medium text-teal-700 hover:text-teal-800">Kembali ke login admin</a></p>
</x-guest-layout>
