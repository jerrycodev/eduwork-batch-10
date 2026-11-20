<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderCancellationRequest;
use App\Models\PaymentReconciliationCheck;
use App\Models\PaymentStatusResolution;
use App\Models\PaymentWebhookLog;
use App\Services\MidtransPaymentService;
use App\Services\OrderEmailService;
use App\Services\OrderNotificationService;
use App\Services\PaymentReconciliationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class PaymentReconciliationController extends Controller
{
    /**
     * Menampilkan daftar data dengan filter dan relasi yang diperlukan untuk kebutuhan operasional.
     */
    public function index(Request $request, PaymentReconciliationService $reconciliation): View
    {
        $validated = $request->validate([
            'severity' => ['nullable', 'in:critical,warning,info'],
            'search' => ['nullable', 'string', 'max:120'],
            'webhook_result' => ['nullable', 'in:processed,rejected,failed'],
            'webhook_status' => ['nullable', 'string', 'max:50'],
        ]);

        $allFindings = $reconciliation->findings();
        $findings = $allFindings
            ->when($validated['severity'] ?? null, fn ($items, string $severity) => $items->where('severity', $severity))
            ->when($validated['search'] ?? null, fn ($items, string $search) => $items->filter(fn (array $finding): bool => str_contains(mb_strtolower($finding['order']->order_number), mb_strtolower($search)) || str_contains(mb_strtolower($finding['order']->user_email), mb_strtolower($search))));

        $counts = $allFindings->countBy('severity');
        $recentChecks = PaymentReconciliationCheck::query()
            ->with('order:id,order_number')
            ->latest('checked_at')
            ->limit(10)
            ->get();
        $webhookLogs = PaymentWebhookLog::query()
            ->with('order:id,order_number,payment_status')
            ->when($validated['webhook_result'] ?? null, fn ($query, string $result) => $query->where('result', $result))
            ->when($validated['webhook_status'] ?? null, fn ($query, string $status) => $query->where('transaction_status', $status))
            ->latest('received_at')->limit(100)->get();
        $webhookCounts = [
            'processed' => PaymentWebhookLog::query()->where('result', 'processed')->count(),
            'rejected' => PaymentWebhookLog::query()->where('result', 'rejected')->count(),
            'failed' => PaymentWebhookLog::query()->where('result', 'failed')->count(),
        ];
        $recentResolutions = PaymentStatusResolution::query()
            ->with(['order:id,order_number', 'admin:id,name,email'])
            ->latest('resolved_at')
            ->limit(10)
            ->get();

        return view('admin.payment-reconciliation.index', compact('findings', 'counts', 'recentChecks', 'recentResolutions', 'webhookLogs', 'webhookCounts'));
    }

    /**
     * Memeriksa konsistensi data dan mencatat hasil pemeriksaan.
     */
    public function check(Order $order, MidtransPaymentService $payments): RedirectResponse
    {
        if (! $order->payment_order_id) {
            return back()->with('error', 'Order ini belum memiliki ID pembayaran Midtrans.');
        }

        try {
            $remote = $payments->fetchStatus($order);
            $remoteOrderId = (string) data_get($remote, 'order_id', '');
            $remoteAmount = (string) data_get($remote, 'gross_amount', '');
            $remoteStatus = strtolower((string) data_get($remote, 'transaction_status', ''));

            if ($remoteOrderId !== $order->payment_order_id) {
                throw new \RuntimeException('Order ID dari respons Midtrans tidak cocok dengan pesanan lokal.');
            }

            $amountIsValid = preg_match('/^\\d+(?:\\.\\d{1,2})?$/D', $remoteAmount) === 1;
            $amountMatches = $amountIsValid && $this->toMinorUnits($remoteAmount) === ((int) $order->total * 100);
            $mappedStatus = $this->mapStatus($remoteStatus, strtolower((string) data_get($remote, 'fraud_status', 'accept')));
            $matched = $amountMatches && $mappedStatus !== null && $mappedStatus === $order->payment_status;

            PaymentReconciliationCheck::query()->create([
                'order_id' => $order->id,
                'checked_by' => auth()->id(),
                'result' => $matched ? 'matched' : 'mismatch',
                'local_status' => $order->payment_status,
                'remote_status' => $remoteStatus,
                'remote_order_id' => $remoteOrderId,
                'remote_transaction_id' => data_get($remote, 'transaction_id'),
                'local_amount' => $order->total,
                'remote_amount' => $amountIsValid ? $remoteAmount : null,
                'message' => ! $amountMatches
                    ? 'Nominal pembayaran Midtrans tidak cocok dengan total pesanan.'
                    : ($mappedStatus === null ? 'Status transaksi Midtrans belum didukung untuk perbandingan otomatis.' : ($matched ? 'Status dan nominal pembayaran cocok.' : 'Status pembayaran Midtrans berbeda dari status lokal.')),
                'checked_at' => now(),
            ]);

            return back()->with($matched ? 'status' : 'error', $matched
                ? 'Verifikasi Midtrans berhasil: status dan nominal pembayaran cocok.'
                : 'Ditemukan perbedaan. Periksa hasil rekonsiliasi; status lokal tidak diubah otomatis.');
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('Midtrans payment reconciliation check failed.', [
                'order_id' => $order->id,
                'exception' => $exception->getMessage(),
            ]);

            PaymentReconciliationCheck::query()->create([
                'order_id' => $order->id,
                'checked_by' => auth()->id(),
                'result' => 'error',
                'local_status' => $order->payment_status,
                'local_amount' => $order->total,
                'message' => mb_substr($exception->getMessage(), 0, 2000),
                'checked_at' => now(),
            ]);

            return back()->with('error', 'Pemeriksaan Midtrans gagal. Lihat riwayat pemeriksaan untuk detailnya.');
        }
    }

    public function resolve(Request $request, Order $order, MidtransPaymentService $payments): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'expected_local_status' => ['required', 'string', 'in:pending,challenge,failed,cancelled,expired'],
            'confirm' => ['required', 'accepted'],
        ]);

        if (! $order->payment_order_id) {
            return back()->with('error', 'Order ini belum memiliki ID pembayaran Midtrans.');
        }

        try {
            // Never trust a status submitted by the browser or an old reconciliation row.
            $remote = $payments->fetchStatus($order);
            $remoteOrderId = (string) data_get($remote, 'order_id', '');
            $remoteAmount = (string) data_get($remote, 'gross_amount', '');
            $remoteStatus = strtolower((string) data_get($remote, 'transaction_status', ''));
            $remoteTransactionId = trim((string) data_get($remote, 'transaction_id', ''));
            $mappedStatus = $this->mapStatus($remoteStatus, strtolower((string) data_get($remote, 'fraud_status', 'accept')));

            if ($remoteOrderId !== $order->payment_order_id) {
                throw new \RuntimeException('Order ID dari respons Midtrans tidak cocok. Status lokal tidak diubah.');
            }

            $amountValid = preg_match('/^\\d+(?:\\.\\d{1,2})?$/D', $remoteAmount) === 1
                && $this->toMinorUnits($remoteAmount) === ((int) $order->total * 100);
            if (! $amountValid) {
                throw new \RuntimeException('Nominal transaksi Midtrans tidak cocok dengan total pesanan. Status lokal tidak diubah.');
            }
            if ($remoteTransactionId === '') {
                throw new \RuntimeException('ID transaksi Midtrans tidak tersedia. Status lokal tidak diubah.');
            }

            // This first release only permits promotion to verified paid; it never downgrades,
            // applies refunds, or changes fulfillment state.
            if ($mappedStatus !== 'paid') {
                throw new \RuntimeException('Status Midtrans bukan pembayaran berhasil yang dapat disinkronkan. Status lokal tidak diubah.');
            }

            $before = null;
            $resolvedOrder = DB::transaction(function () use ($order, $validated, $remote, $remoteStatus, $remoteOrderId, $remoteTransactionId, $remoteAmount, &$before): Order {
                $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
                $before = $lockedOrder->payment_status;

                if ($before !== $validated['expected_local_status']) {
                    throw new \RuntimeException('Status lokal sudah berubah sejak halaman dibuka. Muat ulang dan periksa kembali.');
                }
                if (! in_array($before, ['pending', 'challenge', 'failed', 'cancelled', 'expired'], true)) {
                    throw new \RuntimeException('Status pembayaran lokal tidak memenuhi syarat untuk koreksi.');
                }

                $lockedOrder->forceFill([
                    'payment_status' => 'paid',
                    'transaction_id' => data_get($remote, 'transaction_id') ?: $lockedOrder->transaction_id,
                    'payment_type' => data_get($remote, 'payment_type') ?: $lockedOrder->payment_type,
                    'paid_at' => $lockedOrder->paid_at ?? now(),
                    'payment_payload' => (array) $remote,
                ])->save();

                PaymentStatusResolution::query()->create([
                    'order_id' => $lockedOrder->id,
                    'admin_id' => auth()->id(),
                    'reason' => $validated['reason'],
                    'previous_status' => $before,
                    'new_status' => 'paid',
                    'remote_status' => $remoteStatus,
                    'remote_order_id' => $remoteOrderId,
                    'remote_transaction_id' => $remoteTransactionId,
                    'remote_amount' => $remoteAmount,
                    'resolved_at' => now(),
                ]);

                if ($lockedOrder->status === 'cancelled') {
                    OrderCancellationRequest::query()->firstOrCreate(
                        ['order_id' => $lockedOrder->id],
                        [
                            'user_id' => $lockedOrder->user_id,
                            'reason' => 'other',
                            'description' => 'Pembayaran berhasil diverifikasi setelah pesanan dibatalkan.',
                            'status' => 'approved',
                            'admin_note' => 'Pembayaran terlambat setelah pembatalan. Refund perlu ditinjau dan diproses terpisah.',
                            'reviewed_at' => now(),
                        ],
                    );
                }

                return $lockedOrder->fresh();
            });

            $resolvedOrder->load('orderItems');
            app(OrderNotificationService::class)->paymentSucceeded($resolvedOrder);
            app(OrderEmailService::class)->send($resolvedOrder, 'payment_successful');

            return back()->with('status', 'Status pembayaran berhasil disinkronkan menjadi paid. Status fulfillment tidak diubah.');
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('Admin Midtrans payment resolution rejected.', [
                'order_id' => $order->id,
                'admin_id' => auth()->id(),
                'exception' => $exception->getMessage(),
            ]);

            return back()->with('error', $exception->getMessage());
        }
    }

    private function mapStatus(string $status, string $fraudStatus): ?string
    {
        return match ($status) {
            'settlement' => $fraudStatus === 'deny' ? 'failed' : ($fraudStatus === 'challenge' ? 'challenge' : 'paid'),
            'capture' => $fraudStatus === 'accept' ? 'paid' : ($fraudStatus === 'deny' ? 'failed' : 'challenge'),
            'pending' => 'pending',
            'deny', 'failure' => 'failed',
            'cancel' => 'cancelled',
            'expire' => 'expired',
            'refund' => 'refunded',
            'partial_refund' => 'partially_refunded',
            default => null,
        };
    }

    private function toMinorUnits(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }
}
