<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentReconciliationCheck;
use App\Services\MidtransPaymentService;
use App\Services\PaymentReconciliationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class PaymentReconciliationController extends Controller
{
    public function index(Request $request, PaymentReconciliationService $reconciliation): View
    {
        $validated = $request->validate([
            'severity' => ['nullable', 'in:critical,warning,info'],
            'search' => ['nullable', 'string', 'max:120'],
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

        return view('admin.payment-reconciliation.index', compact('findings', 'counts', 'recentChecks'));
    }

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
