<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-teal-600">Admin workspace</p>
                <h1 class="mt-1 text-xl font-bold tracking-tight text-slate-900">Notifications</h1>
                <p class="mt-1 text-sm text-slate-500">Operational alerts and updates that need your attention.</p>
            </div>
            @if (auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                    @csrf
                    <button class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        <i data-lucide="check-check" class="h-4 w-4"></i>Mark all as read
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="mx-auto w-full max-w-5xl space-y-4 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="font-semibold text-slate-900">All notifications</h2>
                    <p class="mt-0.5 text-sm text-slate-500">{{ $notifications->total() }} total notifications</p>
                </div>
                <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-700">{{ auth()->user()->unreadNotifications()->count() }} unread</span>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($notifications as $notification)
                    <form method="POST" action="{{ route('admin.notifications.read', $notification->id) }}" class="flex items-start gap-4 px-5 py-5 transition {{ $notification->read_at ? 'bg-white' : 'bg-teal-50/50' }} hover:bg-slate-50">
                        @csrf
                        <span class="mt-1.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $notification->read_at ? 'bg-slate-100 text-slate-400' : 'bg-teal-100 text-teal-700' }}">
                            <i data-lucide="{{ $notification->read_at ? 'bell' : 'bell-ring' }}" class="h-4 w-4"></i>
                        </span>
                        <button type="submit" class="min-w-0 flex-1 text-left">
                            <span class="flex flex-wrap items-center gap-2">
                                <span class="font-semibold text-slate-900">{{ data_get($notification->data, 'title', 'Notification') }}</span>
                                @unless ($notification->read_at)
                                    <span class="rounded-full bg-teal-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-teal-700">New</span>
                                @endunless
                            </span>
                            <span class="mt-1 block text-sm leading-6 text-slate-600">{{ data_get($notification->data, 'message') }}</span>
                            <span class="mt-2 block text-xs text-slate-400">{{ $notification->created_at->format('d M Y, H:i') }} · {{ $notification->created_at->diffForHumans() }}</span>
                        </button>
                        <span class="shrink-0 pt-1 text-xs font-semibold text-teal-700">View</span>
                    </form>
                @empty
                    <div class="px-6 py-16 text-center">
                        <span class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400"><i data-lucide="bell" class="h-5 w-5"></i></span>
                        <p class="font-semibold text-slate-800">No notifications yet</p>
                        <p class="mt-1 text-sm text-slate-500">Store and order alerts will appear here.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <div>{{ $notifications->links() }}</div>
    </div>
</x-app-layout>
