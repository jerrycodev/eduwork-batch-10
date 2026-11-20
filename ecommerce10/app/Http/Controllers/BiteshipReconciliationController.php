<?php

namespace App\Http\Controllers;

use App\Models\BiteshipReconciliationCheck;
use App\Models\BiteshipWebhookLog;
use App\Models\Order;
use App\Services\BiteshipShippingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class BiteshipReconciliationController extends Controller
{
    /**
     * Menampilkan daftar data dengan filter dan relasi yang diperlukan untuk kebutuhan operasional.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'result' => ['nullable', 'in:blocked,ready,created,error'],
            'search' => ['nullable', 'string', 'max:120'],
            'webhook_result' => ['nullable', 'in:processed,rejected,failed,handshake'],
            'webhook_event' => ['nullable', 'string', 'max:100'],
        ]);

        $problemOrders = Order::query()
            ->where('payment_status', 'paid')
            ->where('status', 'processing')
            ->whereNull('biteship_order_id')
            ->with(['user:id,name,email', 'biteshipReconciliationChecks' => fn ($query) => $query->limit(1)])
            ->latest('updated_at')
            ->limit(500)
            ->get();

        if (filled($validated['search'] ?? null)) {
            $needle = mb_strtolower($validated['search']);
            $problemOrders = $problemOrders->filter(fn (Order $order): bool => str_contains(mb_strtolower($order->order_number), $needle)
                || str_contains(mb_strtolower((string) $order->user_email), $needle));
        }

        $recentChecks = BiteshipReconciliationCheck::query()
            ->with(['order:id,order_number,status,payment_status', 'checker:id,name'])
            ->when($validated['result'] ?? null, fn ($query, string $result) => $query->where('result', $result))
            ->latest('checked_at')->limit(50)->get();

        $webhookLogs = BiteshipWebhookLog::query()
            ->with('order:id,order_number,status')
            ->when($validated['webhook_result'] ?? null, fn ($query, string $result) => $query->where('result', $result))
            ->when($validated['webhook_event'] ?? null, fn ($query, string $event) => $query->where('event', $event))
            ->latest('received_at')->limit(100)->get();

        $webhookCounts = [
            'processed' => BiteshipWebhookLog::query()->where('result', 'processed')->count(),
            'rejected' => BiteshipWebhookLog::query()->where('result', 'rejected')->count(),
            'failed' => BiteshipWebhookLog::query()->where('result', 'failed')->count(),
            'handshake' => BiteshipWebhookLog::query()->where('result', 'handshake')->count(),
        ];

        $counts = [
            'problem_orders' => $problemOrders->count(),
            'blocked' => $recentChecks->where('result', 'blocked')->count(),
            'ready' => $recentChecks->where('result', 'ready')->count(),
            'created' => $recentChecks->where('result', 'created')->count(),
            'error' => $recentChecks->where('result', 'error')->count(),
        ];

        return view('admin.biteship-reconciliation.index', compact('problemOrders', 'recentChecks', 'counts', 'webhookLogs', 'webhookCounts'));
    }

    /**
     * Memeriksa konsistensi data dan mencatat hasil pemeriksaan.
     */
    public function check(Order $order): RedirectResponse
    {
        $findings = $this->preflight($order);
        $ready = $findings === [];
        BiteshipReconciliationCheck::query()->create([
            'order_id' => $order->id,
            'checked_by' => Auth::id(),
            'result' => $ready ? 'ready' : 'blocked',
            'biteship_order_id' => $order->biteship_order_id,
            'message' => $ready ? 'Pemeriksaan lokal lulus. Pesanan siap dicoba kirim ke Biteship.' : implode("\n", $findings),
            'checked_at' => now(),
        ]);

        return back()->with($ready ? 'status' : 'error', $ready
            ? 'Pemeriksaan RevStore lulus. Silakan coba buat shipment Biteship.'
            : 'Pemeriksaan menemukan masalah: '.implode(' | ', $findings));
    }

    /**
     * Mengulangi proses integrasi setelah memeriksa kondisi dan status terakhir.
     */
    public function retry(Order $order, BiteshipShippingService $biteship): RedirectResponse
    {
        $findings = $this->preflight($order);
        if ($findings !== []) {
            BiteshipReconciliationCheck::query()->create([
                'order_id' => $order->id, 'checked_by' => Auth::id(), 'result' => 'blocked',
                'message' => implode("\n", $findings), 'checked_at' => now(),
            ]);

            return back()->with('error', 'Shipment tidak dikirim karena pemeriksaan RevStore belum lulus: '.implode(' | ', $findings));
        }

        try {
            $created = $biteship->createShipment($order);
            BiteshipReconciliationCheck::query()->create([
                'order_id' => $order->id, 'checked_by' => Auth::id(), 'result' => 'created',
                'biteship_order_id' => $created->biteship_order_id,
                'remote_status' => $created->biteship_status,
                'message' => 'Shipment berhasil dibuat di Biteship.', 'checked_at' => now(),
            ]);

            return back()->with('status', 'Shipment Biteship berhasil dibuat. ID: '.$created->biteship_order_id);
        } catch (Throwable $exception) {
            report($exception);
            Log::error('Biteship shipment reconciliation retry failed.', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'admin_id' => Auth::id(),
                'exception' => $exception->getMessage(),
            ]);
            BiteshipReconciliationCheck::query()->create([
                'order_id' => $order->id, 'checked_by' => Auth::id(), 'result' => 'error',
                'http_status' => preg_match('/HTTP\s+(\d{3})/i', $exception->getMessage(), $statusMatch) ? (int) $statusMatch[1] : null,
                'biteship_order_id' => $order->biteship_order_id,
                'message' => mb_substr($exception->getMessage(), 0, 4000), 'checked_at' => now(),
            ]);

            return back()->with('error', 'Biteship menolak pembuatan shipment: '.$exception->getMessage());
        }
    }

    /** @return array<int, string> */
    private function preflight(Order $order): array
    {
        $issues = [];
        $warehouse = config('biteship.warehouse', []);
        if (blank(config('biteship.api_key'))) {
            $issues[] = 'BITESHIP_API_KEY belum diatur.';
        }
        if (blank(config('biteship.base_url'))) {
            $issues[] = 'BITESHIP_BASE_URL belum diatur.';
        }
        foreach (['name' => 'BITESHIP_WAREHOUSE_NAME', 'phone' => 'BITESHIP_WAREHOUSE_PHONE', 'address' => 'BITESHIP_WAREHOUSE_ADDRESS', 'postal_code' => 'BITESHIP_WAREHOUSE_POSTAL_CODE'] as $key => $env) {
            if (blank($warehouse[$key] ?? null)) {
                $issues[] = $env.' belum diatur.';
            }
        }
        if (filled($warehouse['postal_code'] ?? null) && ! preg_match('/^\\d{5}$/', (string) $warehouse['postal_code'])) {
            $issues[] = 'Kode pos warehouse harus 5 digit.';
        }
        if ($order->payment_status !== 'paid') {
            $issues[] = 'Pembayaran pesanan belum berstatus paid.';
        }
        if ($order->status !== 'processing') {
            $issues[] = 'Status pesanan harus Processing.';
        }
        if ($order->biteship_order_id) {
            $issues[] = 'Shipment sudah memiliki ID Biteship: '.$order->biteship_order_id;
        }
        foreach (['recipient_name' => 'Nama penerima', 'user_phone' => 'Telepon penerima', 'address' => 'Alamat penerima', 'zip' => 'Kode pos penerima', 'shipping_courier_code' => 'Kode kurir', 'shipping_service_code' => 'Kode layanan'] as $key => $label) {
            if (blank($order->{$key})) {
                $issues[] = $label.' pesanan belum tersedia.';
            }
        }
        if (filled($order->zip) && ! preg_match('/^\\d{5}$/', (string) $order->zip)) {
            $issues[] = 'Kode pos penerima harus 5 digit.';
        }
        if ($order->orderItems()->doesntExist()) {
            $issues[] = 'Detail item pesanan kosong.';
        }

        return $issues;
    }
}
