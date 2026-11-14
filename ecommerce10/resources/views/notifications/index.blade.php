<x-layout-component title="Notifikasi">
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Notifikasi</h1>
                <p class="mt-1 text-sm text-gray-500">Informasi pembayaran dan perkembangan pesanan Anda.</p>
            </div>
            @if (auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button class="rounded-full border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Tandai semua dibaca</button>
                </form>
            @endif
        </div>

        <div class="divide-y divide-gray-100 overflow-hidden rounded-2xl border border-gray-200 bg-white">
            @forelse ($notifications as $notification)
                <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="flex items-start gap-4 p-5 {{ $notification->read_at ? '' : 'bg-indigo-50/50' }}">
                    @csrf
                    <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full {{ $notification->read_at ? 'bg-gray-200' : 'bg-indigo-600' }}"></span>
                    <button type="submit" class="min-w-0 flex-1 text-left">
                        <span class="block font-semibold text-gray-900">{{ data_get($notification->data, 'title', 'Notifikasi') }}</span>
                        <span class="mt-1 block text-sm text-gray-600">{{ data_get($notification->data, 'message') }}</span>
                        <span class="mt-2 block text-xs text-gray-400">{{ $notification->created_at->diffForHumans() }}</span>
                    </button>
                    <span class="text-xs font-semibold text-indigo-600">Lihat</span>
                </form>
            @empty
                <div class="p-12 text-center">
                    <p class="font-semibold text-gray-800">Belum ada notifikasi</p>
                    <p class="mt-1 text-sm text-gray-500">Notifikasi pesanan akan muncul di halaman ini.</p>
                </div>
            @endforelse
        </div>
        {{ $notifications->links() }}
    </div>
</x-layout-component>
