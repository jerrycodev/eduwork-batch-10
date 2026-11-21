<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Mengelola akun pelanggan dan administrator dari workspace admin.
 * Aturan keselamatan role (akun sendiri dan admin terakhir) dijaga di server,
 * bukan hanya dengan menyembunyikan tombol pada tampilan.
 */
class AdminUserController extends Controller
{
    /**
     * Menampilkan daftar data dengan filter dan relasi yang diperlukan untuk kebutuhan operasional.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'role' => ['nullable', Rule::in(['admin', 'customer'])],
        ]);

        $users = User::query()
            ->with('creator:id,name')
            ->withCount(['orders', 'carts'])
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->when($validated['role'] ?? null, fn ($query, string $role) => $query->where('role', $role))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => User::query()->count(),
            'admins' => User::query()->where('role', 'admin')->count(),
            'active_admins' => User::query()->where('role', 'admin')->where('is_active', true)->count(),
            'customers' => User::query()->where('role', 'customer')->count(),
            'inactive' => User::query()->where('is_active', false)->count(),
        ];

        return view('admin.users.index', compact('users', 'stats'));
    }

    /**
     * Menyiapkan halaman pembuatan data baru.
     */
    public function create(): View
    {
        return view('admin.users.create', [
            'user' => new User,
            'roles' => $this->roles(),
        ]);
    }

    /** Validasi lalu buat akun baru dengan password yang selalu di-hash. */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['admin', 'customer'])],
            'email_verified' => ['sometimes', 'boolean'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.string' => 'Nama lengkap harus berupa teks.',
            'name.max' => 'Nama lengkap maksimal 255 karakter.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Alamat email tidak valid. Periksa kembali format email.',
            'email.unique' => 'Alamat email sudah digunakan oleh pengguna lain.',
            'email.max' => 'Alamat email maksimal 255 karakter.',
            'role.required' => 'Silakan pilih role pengguna.',
            'role.in' => 'Role pengguna yang dipilih tidak valid.',
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal harus 8 karakter.',
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'created_by' => $validated['role'] === 'admin' ? auth()->id() : null,
            'password' => Hash::make($validated['password']),
        ]);

        if ($request->boolean('email_verified')) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return redirect()->route('admin-users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    /**
     * Menampilkan detail data beserta relasi yang dibutuhkan oleh halaman.
     */
    public function show(User $adminUser): RedirectResponse
    {
        return redirect()->route('admin-users.edit', $adminUser);
    }

    /**
     * Menyiapkan halaman perubahan data yang sudah ada.
     */
    public function edit(User $adminUser): View|RedirectResponse
    {
        if (! $this->canManageAdministrator($adminUser)) {
            return redirect()->route('admin-users.index')->with('error', 'Anda tidak memiliki izin untuk mengelola administrator ini.');
        }

        return view('admin.users.edit', [
            'user' => $adminUser,
            'roles' => $this->roles(),
        ]);
    }

    /** Perbarui profil; perubahan email membatalkan status verifikasi sebelumnya. */
    public function update(Request $request, User $adminUser): RedirectResponse
    {
        if (! $this->canManageAdministrator($adminUser)) {
            return redirect()->route('admin-users.index')->with('error', 'Anda tidak memiliki izin untuk mengelola administrator ini.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($adminUser->id)],
            'role' => ['required', Rule::in(['admin', 'customer'])],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.string' => 'Nama lengkap harus berupa teks.',
            'name.max' => 'Nama lengkap maksimal 255 karakter.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Alamat email tidak valid. Periksa kembali format email.',
            'email.unique' => 'Alamat email sudah digunakan oleh pengguna lain.',
            'email.max' => 'Alamat email maksimal 255 karakter.',
            'role.required' => 'Silakan pilih role pengguna.',
            'role.in' => 'Role pengguna yang dipilih tidak valid.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        if ($adminUser->is(auth()->user()) && $validated['role'] !== 'admin') {
            return back()->withErrors(['role' => 'Anda tidak dapat mengubah role akun yang sedang digunakan.'])->withInput();
        }

        if ($adminUser->role === 'admin' && $validated['role'] !== 'admin' && User::query()->where('role', 'admin')->count() <= 1) {
            return back()->withErrors(['role' => 'Minimal harus ada satu administrator.'])->withInput();
        }

        $emailChanged = $adminUser->email !== $validated['email'];

        $wasAdministrator = $adminUser->role === 'admin';
        $adminUser->name = $validated['name'];
        $adminUser->email = $validated['email'];
        $adminUser->role = $validated['role'];

        if (! $wasAdministrator && $validated['role'] === 'admin') {
            $adminUser->created_by = auth()->id();
        }

        if ($emailChanged) {
            $adminUser->email_verified_at = null;
        }

        if (filled($validated['password'] ?? null)) {
            $adminUser->password = Hash::make($validated['password']);
        }

        $adminUser->save();

        return redirect()->route('admin-users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    /** Aktifkan atau nonaktifkan akun tanpa menghapus riwayat dan relasi pengguna. */
    public function toggleStatus(User $adminUser): RedirectResponse
    {
        if (! $this->canManageAdministrator($adminUser)) {
            return back()->with('error', 'Anda tidak memiliki izin untuk mengelola administrator ini.');
        }

        if ($adminUser->is(auth()->user())) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun yang sedang digunakan.');
        }

        if ($adminUser->role === 'admin' && $adminUser->is_active && User::query()->where('role', 'admin')->where('is_active', true)->count() <= 1) {
            return back()->with('error', 'Administrator aktif terakhir tidak dapat dinonaktifkan.');
        }

        $adminUser->is_active = ! $adminUser->is_active;
        $adminUser->save();

        return redirect()->route('admin-users.index')->with(
            'success',
            $adminUser->is_active
                ? 'Akun pengguna berhasil diaktifkan kembali.'
                : 'Akun pengguna berhasil dinonaktifkan.'
        );
    }

    /** Hapus akun hanya jika bukan akun aktif, bukan admin terakhir, dan tidak memiliki relasi transaksi. */
    public function destroy(User $adminUser): RedirectResponse
    {
        if (! $this->canManageAdministrator($adminUser)) {
            return back()->with('error', 'Anda tidak memiliki izin untuk mengelola administrator ini.');
        }

        if ($adminUser->is(auth()->user())) {
            return back()->with('error', 'Anda tidak dapat menghapus akun yang sedang digunakan.');
        }

        if ($adminUser->role === 'admin' && User::query()->where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Administrator terakhir tidak dapat dihapus.');
        }

        if ($adminUser->role === 'admin' && $adminUser->createdUsers()->where('role', 'admin')->exists()) {
            return back()->with('error', 'Administrator ini masih memiliki administrator turunan. Kelola atau hapus administrator turunannya terlebih dahulu.');
        }

        if ($adminUser->orders()->exists() || $adminUser->carts()->exists()) {
            return back()->with('error', 'Pengguna tidak dapat dihapus karena masih memiliki keranjang atau riwayat pesanan. Ubah role atau data akun jika diperlukan.');
        }

        $adminUser->delete();

        return redirect()->route('admin-users.index')->with('success', 'Pengguna berhasil dihapus.');
    }

    /** Administrator hanya dapat mengelola administrator yang dibuatnya sendiri. */
    private function canManageAdministrator(User $target): bool
    {
        if ($target->role !== 'admin' || $target->is(auth()->user())) {
            return true;
        }

        return $target->created_by === auth()->id();
    }

    /**
     * @return array<string, string>
     */
    private function roles(): array
    {
        return [
            'customer' => 'Pelanggan',
            'admin' => 'Administrator',
        ];
    }
}
