<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap;
use Midtrans\Transaction;
use RuntimeException;

class MidtransPaymentService
{
    public function createPayment(Order $order): Order
    {
        // Serialize payment-session creation so concurrent requests cannot create
        // multiple active Snap sessions for the same order.
        return DB::transaction(function () use ($order): Order {
            $lockedOrder = Order::query()->lockForUpdate()->with('orderItems')->findOrFail($order->id);

            if (in_array($lockedOrder->payment_status, ['paid', 'refunded', 'partially_refunded'], true) || $lockedOrder->status === 'cancelled') {
                throw new RuntimeException('Pesanan ini tidak dapat dibuatkan sesi pembayaran baru.');
            }

            if (
                $lockedOrder->snap_token &&
                $lockedOrder->snap_redirect_url &&
                in_array($lockedOrder->payment_status, ['pending', 'challenge'], true)
            ) {
                return $lockedOrder;
            }

            $this->configure();
            $paymentOrderId = 'PAY-'.$lockedOrder->order_number.'-'.Str::lower(Str::random(6));
            $itemDetails = $lockedOrder->orderItems->map(fn ($item) => [
                'id' => (string) $item->product_id,
                'price' => (int) $item->price,
                'quantity' => (int) $item->quantity,
                'name' => Str::limit($item->product_name, 50, ''),
            ])->values()->all();

            if ((int) $lockedOrder->shipping_cost > 0) {
                $itemDetails[] = [
                    'id' => 'SHIPPING',
                    'price' => (int) $lockedOrder->shipping_cost,
                    'quantity' => 1,
                    'name' => Str::limit('Ongkir '.($lockedOrder->shipping_courier ?? '').' '.($lockedOrder->shipping_service ?? ''), 50, ''),
                ];
            }

            $response = Snap::createTransaction([
                'transaction_details' => [
                    'order_id' => $paymentOrderId,
                    'gross_amount' => (int) $lockedOrder->total,
                ],
                'item_details' => $itemDetails,
                'customer_details' => [
                    'first_name' => $lockedOrder->recipient_name ?: $lockedOrder->user_name,
                    'email' => $lockedOrder->user_email,
                    'phone' => $lockedOrder->user_phone,
                    'shipping_address' => [
                        'first_name' => $lockedOrder->recipient_name ?: $lockedOrder->user_name,
                        'email' => $lockedOrder->user_email,
                        'phone' => $lockedOrder->user_phone,
                        'address' => collect([$lockedOrder->address, $lockedOrder->city, $lockedOrder->state, $lockedOrder->zip, $lockedOrder->country])->filter()->implode(', '),
                        'country_code' => 'IDN',
                    ],
                ],
            ]);

            if (empty($response->token) || empty($response->redirect_url)) {
                throw new RuntimeException('Midtrans tidak mengembalikan token pembayaran yang valid.');
            }

            $lockedOrder->forceFill([
                'payment_order_id' => $paymentOrderId,
                'snap_token' => $response->token,
                'snap_redirect_url' => $response->redirect_url,
                'payment_status' => 'pending',
            ])->save();

            return $lockedOrder->refresh();
        });
    }

    public function fetchStatus(Order $order): object
    {
        if (! $order->payment_order_id) {
            throw new RuntimeException('Order ID pembayaran Midtrans tidak tersedia.');
        }

        $this->configure();

        return (object) Transaction::status($order->payment_order_id);
    }

    public function refund(Order $order, int $amount, string $reason, string $refundKey): object
    {
        if (! $order->transaction_id) {
            throw new RuntimeException('ID transaksi Midtrans tidak tersedia untuk refund otomatis.');
        }

        $transactionStatus = data_get($order->payment_payload, 'transaction_status');
        if ($transactionStatus !== 'settlement') {
            throw new RuntimeException('Refund otomatis hanya tersedia untuk transaksi Midtrans berstatus settlement. Gunakan refund manual.');
        }

        if ($amount < 1 || $amount > (int) $order->total) {
            throw new RuntimeException('Nominal refund tidak valid.');
        }

        $this->configure();

        return (object) Transaction::refund($order->transaction_id, [
            'refund_key' => $refundKey,
            'amount' => $amount,
            'reason' => $reason,
        ]);
    }

    public function configure(): void
    {
        $serverKey = (string) config('midtrans.server_key');

        if ($serverKey === '' || (string) config('midtrans.client_key') === '') {
            throw new RuntimeException('MIDTRANS_SERVER_KEY dan MIDTRANS_CLIENT_KEY wajib dikonfigurasi.');
        }

        MidtransConfig::$serverKey = $serverKey;
        MidtransConfig::$clientKey = (string) config('midtrans.client_key');
        MidtransConfig::$isProduction = (bool) config('midtrans.is_production');
        MidtransConfig::$isSanitized = (bool) config('midtrans.is_sanitized', true);
        MidtransConfig::$is3ds = (bool) config('midtrans.is_3ds', true);
    }

    public function snapJsUrl(): string
    {
        return config('midtrans.is_production')
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
    }
}
