@auth
    <a href="{{ route('notifications.index') }}" data-notification-user="{{ auth()->id() }}"
        @if (config('realtime.enabled') && config('realtime.notifications')) data-realtime-notifications @endif
        class="relative inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 text-gray-600 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700"
        aria-label="Notifikasi">
        <i data-lucide="bell" class="h-5 w-5"></i>
        @php($unreadNotificationCount = auth()->user()->unreadNotifications()->count())
        <span data-notification-count @class([
            'absolute -right-1 -top-1 min-w-5 rounded-full bg-rose-600 px-1.5 py-0.5 text-center text-[10px] font-bold text-white',
            'hidden' => $unreadNotificationCount === 0,
        ])>{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>
    </a>
@endauth
