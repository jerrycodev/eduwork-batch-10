<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\PaymentReconciliationAlert;
use App\Models\PaymentReconciliationCheck;
use App\Models\User;
use App\Notifications\OrderAlert;
use App\Services\MidtransPaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ReconcileMidtransPayments extends Command
{
    protected $signature = 'payments:reconcile {--limit=50 : Maximum number of transactions to check} {--interval=6 : Minimum hours between checks for an order}';

    protected $description = 'Periodically verify recent Midtrans transactions and alert administrators about discrepancies.';

    public function handle(MidtransPaymentService $payments): int
    {
        $limit = max(1, min((int) $this->option('limit'), 200));
        $interval = max(1, min((int) $this->option('interval'), 168));
        $orders = Order::query()
            ->whereNotNull('payment_order_id')
            ->where('created_at', '>=', now()->subDays(30))
            ->whereIn('payment_status', ['pending', 'challenge', 'paid', 'failed', 'cancelled', 'expired', 'refunded', 'partially_refunded'])
            ->whereDoesntHave('paymentReconciliationChecks', fn ($query) => $query->where('checked_at', '>=', now()->subHours($interval)))
            ->orderBy(PaymentReconciliationCheck::query()
                ->select('checked_at')
                ->whereColumn('payment_reconciliation_checks.order_id', 'orders.id')
                ->latest('checked_at')
                ->limit(1))
            ->limit($limit)
            ->get();

        $checked = 0;
        foreach ($orders as $order) {
            try {
                $remote = $payments->fetchStatus($order);
                $remoteOrderId = (string) data_get($remote, 'order_id', '');
                $remoteAmount = (string) data_get($remote, 'gross_amount', '');
                $remoteStatus = strtolower((string) data_get($remote, 'transaction_status', ''));
                if ($remoteOrderId !== $order->payment_order_id) {
                    throw new \RuntimeException('Order ID dari respons Midtrans tidak cocok dengan pesanan lokal.');
                }

                $amountValid = preg_match('/^\\d+(?:\\.\\d{1,2})?$/D', $remoteAmount) === 1;
                $amountMatches = $amountValid && $this->toMinorUnits($remoteAmount) === ((int) $order->total * 100);
                $mappedStatus = $this->mapStatus($remoteStatus, strtolower((string) data_get($remote, 'fraud_status', 'accept')));
                $matched = $amountMatches && $mappedStatus !== null && $mappedStatus === $order->payment_status;
                $message = ! $amountMatches
                    ? 'Nominal pembayaran Midtrans tidak cocok dengan total pesanan.'
                    : ($mappedStatus === null ? 'Status transaksi Midtrans belum didukung untuk perbandingan otomatis.' : ($matched ? 'Status dan nominal pembayaran cocok.' : 'Status pembayaran Midtrans berbeda dari status lokal.'));

                PaymentReconciliationCheck::query()->create([
                    'order_id' => $order->id,
                    'result' => $matched ? 'matched' : 'mismatch',
                    'local_status' => $order->payment_status,
                    'remote_status' => $remoteStatus,
                    'remote_order_id' => $remoteOrderId,
                    'remote_transaction_id' => data_get($remote, 'transaction_id'),
                    'local_amount' => $order->total,
                    'remote_amount' => $amountValid ? $remoteAmount : null,
                    'message' => $message,
                    'checked_at' => now(),
                ]);

                if (! $matched) {
                    $this->alertAdmins($order, 'payment_mismatch', $remoteStatus.'|'.$remoteAmount.'|'.$message, 'Perbedaan pembayaran Midtrans', $message.' Status lokal tidak diubah otomatis.');
                }
            } catch (Throwable $exception) {
                report($exception);
                PaymentReconciliationCheck::query()->create([
                    'order_id' => $order->id,
                    'result' => 'error',
                    'local_status' => $order->payment_status,
                    'local_amount' => $order->total,
                    'message' => mb_substr($exception->getMessage(), 0, 2000),
                    'checked_at' => now(),
                ]);
                $this->alertAdmins($order, 'payment_check_error', $exception->getMessage(), 'Pemeriksaan Midtrans gagal', 'Gagal memeriksa transaksi: '.$exception->getMessage());
            }
            $checked++;
        }

        $this->info("{$checked} transaksi diperiksa.");

        return self::SUCCESS;
    }

    private function alertAdmins(Order $order, string $code, string $details, string $title, string $message): void
    {
        $fingerprint = hash('sha256', $code.'|'.$details);
        $shouldNotify = DB::transaction(function () use ($order, $code, $fingerprint): bool {
            $alert = PaymentReconciliationAlert::query()
                ->where('order_id', $order->id)
                ->where('finding_code', $code)
                ->lockForUpdate()
                ->first();

            if ($alert && $alert->fingerprint === $fingerprint) {
                return false;
            }

            PaymentReconciliationAlert::query()->updateOrCreate(
                ['order_id' => $order->id, 'finding_code' => $code],
                ['fingerprint' => $fingerprint, 'notified_at' => now()],
            );

            return true;
        });

        if (! $shouldNotify) {
            return;
        }

        $admins = User::query()->where('role', 'admin')->get();
        Notification::send($admins, new OrderAlert([
            'type' => 'payment_reconciliation_alert',
            'title' => $title,
            'message' => 'Pesanan '.$order->order_number.': '.$message,
            'url' => route('payment-reconciliation.index'),
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
            'finding_code' => $code,
        ]));
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
