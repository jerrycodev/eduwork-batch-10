<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\ExpireUnpaidOrder;
use App\Services\MidtransPaymentService;
use Illuminate\Console\Command;
use Throwable;

class ExpireUnpaidOrders extends Command
{
    protected $signature = 'orders:expire-unpaid {--limit=100 : Maximum number of due orders to process}';

    protected $description = 'Cancel expired unpaid orders and safely release their reserved stock.';

    /**
     * Menjalankan alur bisnis untuk menangani event atau permintaan yang diterima.
     */
    public function handle(
        MidtransPaymentService $payments,
        ExpireUnpaidOrder $expiry,
    ): int {
        $limit = max(1, min((int) $this->option('limit'), 500));
        $orders = Order::query()
            ->where('status', 'pending')
            ->whereIn('payment_status', ['unpaid', 'pending', 'challenge', 'failed'])
            ->whereNotNull('payment_expires_at')
            ->where('payment_expires_at', '<=', now())
            ->orderBy('payment_expires_at')
            ->limit($limit)
            ->get();

        $cancelled = 0;
        $deferred = 0;

        foreach ($orders as $order) {
            try {
                // No Midtrans transaction was created: it is safe to release the reservation.
                if (! $order->payment_order_id) {
                    $cancelled += (int) $expiry->handle($order);

                    continue;
                }

                $remote = $payments->fetchStatus($order);
                $remoteStatus = strtolower((string) data_get($remote, 'transaction_status', ''));

                if (! in_array($remoteStatus, ['pending', 'expire', 'cancel', 'deny', 'failure'], true)) {
                    // Settlement/capture or another non-pending state must never release stock here.
                    $deferred++;

                    continue;
                }

                if ($remoteStatus === 'pending') {
                    $payments->cancelPending($order);
                    $remote = $payments->fetchStatus($order);
                    $remoteStatus = strtolower((string) data_get($remote, 'transaction_status', ''));
                }

                if (in_array($remoteStatus, ['expire', 'cancel', 'deny', 'failure'], true)) {
                    $paymentStatus = match ($remoteStatus) {
                        'cancel' => 'cancelled',
                        'deny', 'failure' => 'failed',
                        default => 'expired',
                    };
                    $cancelled += (int) $expiry->handle($order, $paymentStatus);
                } else {
                    // Keep stock reserved if Midtrans has not confirmed cancellation yet.
                    $deferred++;
                }
            } catch (Throwable $exception) {
                // Midtrans 404 with this exact response means the configured merchant
                // has no transaction for this order ID. There is nothing left to cancel
                // remotely, so the expired local reservation can safely be released.
                if (
                    (int) $exception->getCode() === 404 &&
                    str_contains($exception->getMessage(), "Transaction doesn't exist")
                ) {
                    report($exception);
                    $cancelled += (int) $expiry->handle($order, 'expired');
                    $this->warn('Order '.$order->order_number.': transaksi tidak ditemukan di Midtrans; reservasi dibatalkan dan stok dikembalikan.');

                    continue;
                }

                report($exception);
                $this->warn('Order '.$order->order_number.' ditunda: '.$exception->getMessage());
                $deferred++;
            }
        }

        $this->info("{$cancelled} pesanan dibatalkan; {$deferred} pesanan ditunda untuk verifikasi.");

        return self::SUCCESS;
    }
}
