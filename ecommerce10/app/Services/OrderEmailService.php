<?php

namespace App\Services;

use App\Mail\OrderTransactionalMail;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;
use Throwable;

class OrderEmailService
{
    public function send(Order $order, string $type): void
    {
        if (blank($order->user_email)) {
            return;
        }

        try {
            Mail::to($order->user_email)->queue(
                new OrderTransactionalMail($order->loadMissing('orderItems'), $type),
            );
        } catch (Throwable $exception) {
            report($exception);
            logger()->error('Unable to queue transactional order email.', [
                'order_id' => $order->id,
                'type' => $type,
                'exception' => $exception->getMessage(),
            ]);
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
