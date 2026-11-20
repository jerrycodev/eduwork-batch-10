<?php

namespace App\Http\Controllers;

use App\Models\EmailDeliveryLog;
use App\Services\OrderEmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailDeliveryLogController extends Controller
{
    /**
     * Menampilkan daftar data dengan filter dan relasi yang diperlukan untuk kebutuhan operasional.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:queued,sent,failed'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $logs = EmailDeliveryLog::query()
            ->with('order:id,order_number')
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('recipient', 'like', '%'.$search.'%')
                        ->orWhere('type', 'like', '%'.$search.'%')
                        ->orWhereHas('order', fn ($orders) => $orders->where('order_number', 'like', '%'.$search.'%'));
                });
            })
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['date_from'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($validated['date_to'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.email-delivery-logs.index', compact('logs'));
    }

    /**
     * Mengulangi proses integrasi setelah memeriksa kondisi dan status terakhir.
     */
    public function retry(EmailDeliveryLog $emailDeliveryLog, OrderEmailService $emails): RedirectResponse
    {
        abort_unless($emailDeliveryLog->status === 'failed', 409, 'Only failed emails can be retried.');

        $emails->retry($emailDeliveryLog);

        return back()->with('status', 'Email dijadwalkan ulang untuk dikirim.');
    }
}
