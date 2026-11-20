<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Menampilkan daftar data dengan filter dan relasi yang diperlukan untuk kebutuhan operasional.
     */
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->user()->role === 'admin') {
            return redirect()->route('admin.notifications.index');
        }

        $notifications = $request->user()->notifications()->latest()->paginate(20);

        return view('notifications.customer.index', compact('notifications'));
    }

    /**
     * Menampilkan data operasional untuk administrator beserta ringkasan dan filter tindak lanjut.
     */
    public function adminIndex(Request $request): View
    {
        $notifications = $request->user()->notifications()->latest()->paginate(20);

        return view('notifications.admin.index', compact('notifications'));
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();

        $fallback = $request->user()->role === 'admin'
            ? route('admin.notifications.index', [], false)
            : route('notifications.index', [], false);

        $notificationUrl = (string) data_get($item->data, 'url', $fallback);

        // Keep notification navigation on the current host. This also supports
        // older records that stored an absolute URL from another environment.
        $path = parse_url($notificationUrl, PHP_URL_PATH);
        $query = parse_url($notificationUrl, PHP_URL_QUERY);
        $target = is_string($path) && str_starts_with($path, '/') && ! str_starts_with($path, '//')
            ? $path.($query ? '?'.$query : '')
            : $fallback;

        return redirect()->to($target);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
