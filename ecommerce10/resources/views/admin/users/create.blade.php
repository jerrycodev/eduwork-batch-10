{{-- Dokumentasi: Halaman pembuatan akun; field dikelola oleh komponen users._form. --}}
<x-app-layout>
    <x-slot name="header">
        <div><a href="{{ route('admin-users.index') }}" class="text-xs font-semibold text-teal-700 hover:text-teal-800">←
                Manajemen pengguna</a>
            <h1 class="mt-2 text-xl font-bold tracking-tight text-slate-900">Tambah pengguna</h1>
            <p class="mt-1 text-sm text-slate-500">Buat akun pelanggan atau administrator baru.</p>
        </div>
    </x-slot>
    <div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            <div class="mb-6 flex items-center gap-3"><span
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-50 text-teal-700"><i
                        data-lucide="user-plus" class="h-5 w-5"></i></span>
                <div>
                    <h2 class="font-semibold text-slate-900">Informasi akun</h2>
                    <p class="mt-1 text-sm text-slate-500">Lengkapi detail pengguna di bawah ini.</p>
                </div>
            </div>@include('admin.users._form')
        </section>
    </div>
</x-app-layout>
