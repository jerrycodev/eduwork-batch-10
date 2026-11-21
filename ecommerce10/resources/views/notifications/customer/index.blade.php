<x-storefront-layout title="Notifikasi">
    <div class="container">
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="mb-2 text-xs font-semibold uppercase tracking-[0.16em] text-primary-700">Akun Saya</p>
                <h1 class="text-2xl font-bold tracking-tight text-gray-950 sm:text-3xl">Notifikasi</h1>
                <p class="mt-2 text-sm leading-6 text-gray-500">Informasi pembayaran dan perkembangan pesanan Anda.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if (auth()->user()->unreadNotifications()->exists())
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <x-ui.button type="submit" variant="secondary" size="sm">Tandai semua dibaca</x-ui.button>
                    </form>
                @endif
                @if ($notifications->total() > 0)
                    <form method="POST" action="{{ route('notifications.destroy-all') }}"
                        onsubmit="return confirm('Hapus semua notifikasi? Tindakan ini tidak dapat dibatalkan.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-rose-200 bg-white px-4 py-2 text-sm font-semibold text-rose-600 shadow-sm transition hover:border-rose-300 hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">
                            Hapus semua
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
        @endif

        <div class="divide-y divide-gray-100 overflow-hidden rounded-2xl border border-gray-200 bg-white">
            @forelse ($notifications as $notification)
                <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="flex items-start gap-4 p-5 {{ $notification->read_at ? '' : 'bg-primary-50/50' }}">
                    @csrf
                    <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full {{ $notification->read_at ? 'bg-gray-200' : 'bg-primary-600' }}"></span>
                    <button type="submit" class="min-w-0 flex-1 text-left">
                        <span class="block font-semibold text-gray-900">{{ data_get($notification->data, 'title', 'Notifikasi') }}</span>
                        <span class="mt-1 block text-sm text-gray-600">{{ data_get($notification->data, 'message') }}</span>
                        <span class="mt-2 block text-xs text-gray-400">{{ $notification->created_at->diffForHumans() }}</span>
                    </button>
                    <span class="text-xs font-semibold text-primary-600">Lihat</span>
                </form>
            @empty
                <div class="p-12 text-center">
                    <span class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400"><i data-lucide="bell" class="h-5 w-5"></i></span>
                    <p class="font-semibold text-gray-800">Belum ada notifikasi</p>
                    <p class="mt-1 text-sm text-gray-500">Notifikasi pesanan akan muncul di halaman ini.</p>
                </div>
            @endforelse
        </div>
        {{ $notifications->links() }}
    </div>
    </div>
</x-storefront-layout>
