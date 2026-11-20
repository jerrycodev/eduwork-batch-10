<?php

namespace App\Services;

use App\Mail\OrderTransactionalMail;
use App\Models\EmailDeliveryLog;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class OrderEmailService
{
    /**
     * Mengirim email atau notifikasi transaksional sesuai jenis peristiwa.
     */
    public function send(Order $order, string $type): void
    {
        if (blank($order->user_email)) {
            return;
        }

        $log = EmailDeliveryLog::query()->create([
            'order_id' => $order->id,
            'recipient' => $order->user_email,
            'type' => $type,
            'status' => 'queued',
            'queued_at' => now(),
        ]);

        $this->queue($order, $log);
    }

    /**
     * Mengulangi proses integrasi setelah memeriksa kondisi dan status terakhir.
     */
    public function retry(EmailDeliveryLog $log): void
    {
        $log = DB::transaction(function () use ($log): EmailDeliveryLog {
            $locked = EmailDeliveryLog::query()->lockForUpdate()->findOrFail($log->id);
            abort_unless($locked->status === 'failed', 409, 'Only failed emails can be retried.');

            $locked->forceFill([
                'status' => 'queued',
                'retry_count' => $locked->retry_count + 1,
                'queued_at' => now(),
                'failed_at' => null,
                'last_error' => null,
            ])->save();

            return $locked;
        });

        $order = $log->order;

        if (! $order) {
            app(EmailDeliveryTracker::class)->failed($log->id, new \RuntimeException('Order no longer exists.'));

            return;
        }

        $this->queue($order, $log);
    }

    private function queue(Order $order, EmailDeliveryLog $log): void
    {
        try {
            Mail::to($log->recipient)->queue(
                new OrderTransactionalMail($order->loadMissing('orderItems'), $log->type, $log->id),
            );
        } catch (Throwable $exception) {
            app(EmailDeliveryTracker::class)->failed($log->id, $exception);
            report($exception);
        }
    }

    public function statusUpdated(Order $order, bool $trackingChanged = false): void
    {
        $type = match (true) {
            $order->status === 'processing' => 'order_processing',
            $order->status === 'shipped' && ($trackingChanged || filled($order->tracking_number)) => 'order_shipped',
            $order->status === 'delivered' => 'order_delivered',
            default => null,
        };

        if ($type !== null) {
            $this->send($order, $type);
        }
    }
}
