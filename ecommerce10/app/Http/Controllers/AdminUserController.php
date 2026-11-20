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
            'customers' => User::query()->where('role', 'customer')->count(),
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
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

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
    public function edit(User $adminUser): View
    {
        return view('admin.users.edit', [
            'user' => $adminUser,
            'roles' => $this->roles(),
        ]);
    }

    /** Perbarui profil; perubahan email membatalkan status verifikasi sebelumnya. */
    public function update(Request $request, User $adminUser): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($adminUser->id)],
            'role' => ['required', Rule::in(['admin', 'customer'])],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        if ($adminUser->is(auth()->user()) && $validated['role'] !== 'admin') {
            return back()->withErrors(['role' => 'Anda tidak dapat mengubah role akun yang sedang digunakan.'])->withInput();
        }

        if ($adminUser->role === 'admin' && $validated['role'] !== 'admin' && User::query()->where('role', 'admin')->count() <= 1) {
            return back()->withErrors(['role' => 'Minimal harus ada satu administrator.'])->withInput();
        }

        $emailChanged = $adminUser->email !== $validated['email'];

        $adminUser->name = $validated['name'];
        $adminUser->email = $validated['email'];
        $adminUser->role = $validated['role'];

        if ($emailChanged) {
            $adminUser->email_verified_at = null;
        }

        if (filled($validated['password'] ?? null)) {
            $adminUser->password = Hash::make($validated['password']);
        }

        $adminUser->save();

        return redirect()->route('admin-users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    /** Hapus akun hanya jika bukan akun aktif, bukan admin terakhir, dan tidak memiliki relasi transaksi. */
    public function destroy(User $adminUser): RedirectResponse
    {
        if ($adminUser->is(auth()->user())) {
            return back()->with('error', 'Anda tidak dapat menghapus akun yang sedang digunakan.');
        }

        if ($adminUser->role === 'admin' && User::query()->where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Administrator terakhir tidak dapat dihapus.');
        }

        if ($adminUser->orders()->exists() || $adminUser->carts()->exists()) {
            return back()->with('error', 'Pengguna tidak dapat dihapus karena masih memiliki keranjang atau riwayat pesanan. Ubah role atau data akun jika diperlukan.');
        }

        $adminUser->delete();

        return redirect()->route('admin-users.index')->with('success', 'Pengguna berhasil dihapus.');
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
