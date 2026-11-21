{{-- Dokumentasi: Manajemen akun, role, verifikasi email, dan aktivitas pesanan pengguna. --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-teal-600">Workspace management</p>
                <h1 class="mt-1 text-xl font-bold tracking-tight text-slate-900">Manajemen Pengguna</h1>
                <p class="mt-1 text-sm text-slate-500">Kelola akun pelanggan dan administrator RevStore.</p>
            </div>
            <a href="{{ route('admin-users.create') }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700">
                <i data-lucide="user-plus" class="h-4 w-4"></i> Tambah pengguna
            </a>
        </div>
    </x-slot>

    <div class="mx-auto flex w-full max-w-[1600px] flex-col gap-5 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        @if (session('success'))
            <x-alert type="success">{{ session('success') }}</x-alert>
        @endif
        @if (session('error'))
            <x-alert type="error">{{ session('error') }}</x-alert>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Total pengguna</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stats['total']) }}
                </p>
            </section>
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Administrator</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-teal-700">{{ number_format($stats['admins']) }}
                </p>
            </section>
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Pelanggan</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                    {{ number_format($stats['customers']) }}</p>
            </section>
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Diblokir / Nonaktif</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-red-600">
                    {{ number_format($stats['inactive']) }}</p>
            </section>
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <form method="GET" action="{{ route('admin-users.index') }}"
                class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row">
                <div class="relative min-w-0 flex-1">
                    <i data-lucide="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input type="search" name="search" value="{{ request('search') }}"
                        placeholder="Cari nama atau email pengguna..."
                        class="w-full rounded-xl border-slate-200 py-2.5 pl-9 pr-3 text-sm focus:border-teal-500 focus:ring-teal-500">
                </div>
                <select name="role"
                    class="rounded-xl border-slate-200 py-2.5 text-sm focus:border-teal-500 focus:ring-teal-500">
                    <option value="">Semua role</option>
                    <option value="customer" @selected(request('role') === 'customer')>Pelanggan</option>
                    <option value="admin" @selected(request('role') === 'admin')>Administrator</option>
                </select>
                <button
                    class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Filter</button>
                @if (request()->hasAny(['search', 'role']))
                    <a href="{{ route('admin-users.index') }}"
                        class="rounded-xl px-3 py-2.5 text-center text-sm font-semibold text-slate-500 hover:bg-slate-50">Reset</a>
                @endif
            </form>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1040px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3.5 font-semibold">Pengguna</th>
                            <th class="px-5 py-3.5 font-semibold">Role</th>
                            <th class="px-5 py-3.5 font-semibold">Dibuat oleh</th>
                            <th class="px-5 py-3.5 font-semibold">Status email</th>
                            <th class="px-5 py-3.5 font-semibold">Status akun</th>
                            <th class="px-5 py-3.5 font-semibold">Pesanan</th>
                            <th class="px-5 py-3.5 font-semibold">Terdaftar</th>
                            <th class="px-5 py-3.5 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($users as $listedUser)
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3"><span
                                            class="{{ $listedUser->role === 'admin' ? 'bg-teal-50 text-teal-700' : 'bg-slate-100 text-slate-600' }} flex h-10 w-10 shrink-0 items-center justify-center rounded-full font-bold">{{ strtoupper(substr($listedUser->name, 0, 1)) }}</span>
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-slate-900">{{ $listedUser->name }}
                                                @if ($listedUser->is(auth()->user()))
                                                    <span class="ml-1 text-xs font-medium text-teal-600">(Anda)</span>
                                                @endif
                                            </p>
                                            <p class="truncate text-xs text-slate-500">{{ $listedUser->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4"><span
                                        class="{{ $listedUser->role === 'admin' ? 'bg-teal-50 text-teal-700' : 'bg-slate-100 text-slate-600' }} rounded-full px-2.5 py-1 text-xs font-semibold">{{ $listedUser->role === 'admin' ? 'Administrator' : 'Pelanggan' }}</span>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    @if ($listedUser->role === 'admin')
                                        <span class="text-xs">{{ $listedUser->creator?->name ?? 'Administrator utama' }}</span>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    @if ($listedUser->email_verified_at)
                                        <span
                                            class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-700"><i
                                                data-lucide="badge-check" class="h-4 w-4"></i>
                                        Terverifikasi</span>@else<span
                                            class="inline-flex items-center gap-1.5 text-xs font-medium text-amber-700"><i
                                                data-lucide="clock-3" class="h-4 w-4"></i> Belum verifikasi</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    @if ($listedUser->is_active)
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Aktif</span>
                                    @else
                                        <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">Diblokir</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ number_format($listedUser->orders_count) }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-500">
                                    {{ $listedUser->created_at?->format('d M Y') }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        @php
                                            $canManageListedUser = $listedUser->role !== 'admin'
                                                || $listedUser->created_by === auth()->id()
                                                || $listedUser->is(auth()->user());
                                            $canToggleStatus = $canManageListedUser
                                                && ! $listedUser->is(auth()->user())
                                                && ! ($listedUser->role === 'admin' && $listedUser->is_active && $stats['active_admins'] <= 1);
                                            $hasAdminChildren = $listedUser->role === 'admin'
                                                && $listedUser->createdUsers()->where('role', 'admin')->exists();
                                        @endphp
                                        @if ($canToggleStatus)
                                            <form method="POST" action="{{ route('admin-users.toggle-status', $listedUser) }}"
                                                x-data="adminConfirm(@js(($listedUser->is_active ? 'Nonaktifkan' : 'Aktifkan kembali') . ' akun ' . $listedUser->name . '?'))"
                                                @submit="submit($event)">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                    class="rounded-lg p-2 {{ $listedUser->is_active ? 'text-amber-600 hover:bg-amber-50 hover:text-amber-700' : 'text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700' }}"
                                                    aria-label="{{ $listedUser->is_active ? 'Nonaktifkan' : 'Aktifkan kembali' }} {{ $listedUser->name }}"
                                                    title="{{ $listedUser->is_active ? 'Nonaktifkan pengguna' : 'Aktifkan kembali pengguna' }}">
                                                    <i data-lucide="{{ $listedUser->is_active ? 'user-round-x' : 'user-round-check' }}" class="h-4 w-4"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span class="rounded-lg p-2 text-slate-300" title="{{ !$canManageListedUser ? 'Hanya administrator pembuat akun yang dapat mengubah status' : ($listedUser->is(auth()->user()) ? 'Akun yang sedang digunakan tidak dapat dinonaktifkan' : 'Administrator aktif terakhir tidak dapat dinonaktifkan') }}">
                                                <i data-lucide="shield-check" class="h-4 w-4"></i>
                                            </span>
                                        @endif
                                        @if ($canManageListedUser)
                                            <a href="{{ route('admin-users.edit', $listedUser) }}"
                                                class="rounded-lg p-2 text-slate-500 transition hover:bg-teal-50 hover:text-teal-700"
                                                aria-label="Edit {{ $listedUser->name }}" title="Edit pengguna"><i
                                                    data-lucide="pencil" class="h-4 w-4"></i></a>
                                        @else
                                            <span class="rounded-lg p-2 text-slate-300" title="Hanya administrator yang membuat akun ini yang dapat mengelolanya">
                                                <i data-lucide="lock" class="h-4 w-4"></i>
                                            </span>
                                        @endif
                                        @if (
                                            $canManageListedUser &&
                                                !$listedUser->is(auth()->user()) &&
                                                $listedUser->orders_count === 0 &&
                                                $listedUser->carts_count === 0 &&
                                                !($listedUser->role === 'admin' && $stats['admins'] <= 1) &&
                                                !$hasAdminChildren)
                                            <form method="POST"
                                                action="{{ route('admin-users.destroy', $listedUser) }}"
                                                x-data="adminConfirm(@js('Hapus akun ' . $listedUser->name . ' secara permanen?'))" @submit="submit($event)">@csrf
                                                @method('DELETE')<button
                                                    class="rounded-lg p-2 text-slate-500 transition hover:bg-red-50 hover:text-red-600"
                                                    aria-label="Hapus {{ $listedUser->name }}"
                                                    title="Hapus pengguna"><i data-lucide="trash-2"
                                                        class="h-4 w-4"></i></button></form>
                                        @else
                                            <span class="rounded-lg p-2 text-slate-300"
                                                title="{{ !$canManageListedUser ? 'Hanya pembuat akun yang dapat menghapus administrator ini' : ($hasAdminChildren ? 'Administrator ini masih memiliki administrator turunan' : 'Akun ini tidak dapat dihapus') }}"><i data-lucide="shield"
                                                    class="h-4 w-4"></i></span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-14 text-center text-slate-500">Tidak ada pengguna yang
                                    cocok dengan filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($users->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">{{ $users->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
