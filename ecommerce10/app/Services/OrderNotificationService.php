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
        $lateAfterCancellation = $order->status === 'cancelled';

        $order->user?->notify(new OrderAlert([
            'type' => $lateAfterCancellation ? 'late_payment_after_cancellation' : 'payment_success',
            'title' => $lateAfterCancellation ? 'Pembayaran diterima setelah pesanan dibatalkan' : 'Pembayaran berhasil',
            'message' => $lateAfterCancellation
                ? 'Pembayaran untuk pesanan '.$order->order_number.' berhasil diterima setelah pesanan dibatalkan. Admin akan menindaklanjuti proses refund.'
                : 'Pembayaran untuk pesanan '.$order->order_number.' telah berhasil diverifikasi.',
            'url' => route('orders.customer.show', $order, false),
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
        ]));

        $admins = User::query()->where('role', 'admin')->get();
        Notification::send($admins, new OrderAlert([
            'type' => $lateAfterCancellation ? 'late_payment_after_cancellation' : 'new_paid_order',
            'title' => $lateAfterCancellation ? 'Refund diperlukan untuk pembayaran terlambat' : 'Pesanan baru perlu diproses',
            'message' => $lateAfterCancellation
                ? 'Pesanan '.$order->order_number.' sudah dibatalkan tetapi pembayaran berhasil diterima. Periksa transaksi dan proses refund secara manual.'
                : 'Pesanan '.$order->order_number.' telah dibayar dan menunggu diproses.',
            'url' => route('orders.show', $order, false),
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
        ]));
    }

    public function paymentExpired(Order $order): void
    {
        $order->loadMissing('user');
        $paymentStatus = (string) $order->payment_status;
        $paymentMessage = match ($paymentStatus) {
            'cancelled' => 'Transaksi pembayaran dibatalkan setelah batas waktu reservasi berakhir.',
            'failed' => 'Transaksi pembayaran gagal setelah batas waktu reservasi berakhir.',
            default => 'Batas waktu pembayaran telah berakhir.',
        };

        $order->user?->notify(new OrderAlert([
            'type' => 'payment_expired',
            'title' => 'Batas waktu pembayaran berakhir',
            'message' => 'Pesanan '.$order->order_number.' dibatalkan otomatis. '.$paymentMessage.' Stok telah dikembalikan.',
            'url' => route('orders.customer.show', $order, false),
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
            'status' => 'cancelled',
            'payment_status' => $paymentStatus,
        ]));

        $admins = User::query()->where('role', 'admin')->get();
        Notification::send($admins, new OrderAlert([
            'type' => 'payment_expired',
            'title' => 'Pesanan dibatalkan otomatis',
            'message' => 'Pesanan '.$order->order_number.' dibatalkan otomatis setelah batas waktu pembayaran berakhir (status transaksi: '.$paymentStatus.'). Stok telah dikembalikan.',
            'url' => route('orders.show', $order, false),
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
            'status' => 'cancelled',
            'payment_status' => $paymentStatus,
        ]));
    }

    public function deliveryFollowUp(Order $order): void
    {
        $admins = User::query()->where('role', 'admin')->get();

        Notification::send($admins, new OrderAlert([
            'type' => 'delivery_follow_up',
            'title' => 'Pesanan melewati estimasi pengiriman',
            'message' => 'Pesanan '.$order->order_number.' belum ditandai diterima customer. Estimasi sampai: '.$order->estimated_delivery_at?->format('d M Y').'. Mohon periksa status kurir dan hubungi customer.',
            'url' => route('orders.show', $order, false),
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'estimated_delivery_at' => $order->estimated_delivery_at?->toIso8601String(),
            'reminder_count' => (int) $order->delivery_follow_up_reminder_count,
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
            'url' => route('orders.customer.show', $order, false),
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
        ]));
    }
}
