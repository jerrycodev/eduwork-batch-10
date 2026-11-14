<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderAlert;
use Illuminate\Support\Facades\Notification;

class OrderNotificationService
{
    public function paymentSucceeded(Order $order): void
    {
        $order->loadMissing('user');

        $order->user?->notify(new OrderAlert([
            'type' => 'payment_success',
            'title' => 'Pembayaran berhasil',
            'message' => 'Pembayaran untuk pesanan '.$order->order_number.' telah berhasil diverifikasi.',
            'url' => route('orders.customer.show', $order),
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
        ]));

        $admins = User::query()->where('role', 'admin')->get();
        Notification::send($admins, new OrderAlert([
            'type' => 'new_paid_order',
            'title' => 'Pesanan baru perlu diproses',
            'message' => 'Pesanan '.$order->order_number.' telah dibayar dan menunggu diproses.',
            'url' => route('orders.show', $order),
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
        ]));
    }

    public function statusUpdated(Order $order, bool $trackingChanged = false): void
    {
        $order->loadMissing('user');

        $order->user?->notify(new OrderAlert([
            'type' => $trackingChanged && $order->status === 'shipped' ? 'order_tracking_updated' : 'order_status_updated',
            'title' => $trackingChanged && $order->status === 'shipped' ? 'Nomor resi pesanan diperbarui' : 'Status pesanan diperbarui',
            'message' => $trackingChanged && $order->status === 'shipped'
                ? 'Pesanan '.$order->order_number.' telah dikirim. Nomor resi: '.$order->tracking_number.'.'
                : 'Pesanan '.$order->order_number.' sekarang berstatus '.str($order->status)->headline().'.',
            'url' => route('orders.customer.show', $order),
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
        ]));
    }
}
