<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderCancellationRequest;
use App\Services\MidtransPaymentService;
use App\Services\OrderEmailService;
use App\Services\OrderNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function __construct(private readonly MidtransPaymentService $payments) {}

    public function checkout(): RedirectResponse
    {
        return redirect()->route('cart')
            ->with('info', 'Silakan lanjutkan checkout melalui keranjang belanja.');
    }

    public function create(Order $order): RedirectResponse
    {
        abort_unless((string) $order->user_id === (string) auth()->id(), 403);

        if (in_array($order->payment_status, ['paid', 'refunded'], true) || $order->status === 'cancelled') {
            return redirect()->route('orders.customer.show', $order)
                ->with('success', 'Pembayaran pesanan ini sudah berhasil.');
        }

        try {
            $this->payments->createPayment($order->load('orderItems'));

            return redirect()->route('orders.customer.show', $order)
                ->with('success', 'Halaman pembayaran berhasil disiapkan.');
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('orders.customer.show', $order)
                ->with('error', 'Pembayaran belum dapat disiapkan. Periksa konfigurasi Midtrans lalu coba lagi.');
        }
    }

    public function callback(Request $request): JsonResponse
    {
        $serverKey = (string) config('midtrans.server_key');
        $orderId = (string) $request->input('order_id');
        $statusCode = (string) $request->input('status_code');
        $grossAmount = (string) $request->input('gross_amount');
        $signatureKey = (string) $request->input('signature_key');

        if ($serverKey === '' || $orderId === '' || $statusCode === '' || $grossAmount === '' || $signatureKey === '') {
            logger()->warning('Midtrans callback ignored: incomplete notification payload.');

            return response()->json(['status' => 'ignored', 'reason' => 'incomplete_payload']);
        }

        $expectedSignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        if (! hash_equals($expectedSignature, $signatureKey)) {
            logger()->warning('Midtrans callback ignored: signature verification failed.', ['order_id' => $orderId]);

            return response()->json(['status' => 'ignored', 'reason' => 'invalid_signature']);
        }

        $notification = $request->all();
        $order = Order::query()->where('payment_order_id', $orderId)->first();

        if (! $order) {
            logger()->info('Midtrans callback received for unknown order.', ['order_id' => $orderId]);

            return response()->json(['status' => 'ignored', 'reason' => 'order_not_found']);
        }

        if (! preg_match('/^\\d+(?:\\.\\d{1,2})?$/D', $grossAmount)) {
            logger()->warning('Midtrans callback ignored: invalid gross amount format.', ['order_id' => $orderId]);

            return response()->json(['status' => 'ignored', 'reason' => 'amount_mismatch']);
        }
        [$wholeAmount, $fractionalAmount] = array_pad(explode('.', $grossAmount, 2), 2, '0');
        $grossAmountInMinorUnits = ((int) $wholeAmount * 100) + (int) str_pad($fractionalAmount, 2, '0');
        if ($grossAmountInMinorUnits !== ((int) $order->total * 100)) {
            logger()->warning('Midtrans callback ignored: gross amount mismatch.', ['order_id' => $orderId]);

            return response()->json(['status' => 'ignored', 'reason' => 'amount_mismatch']);
        }

        $merchantId = (string) config('midtrans.merchant_id');
        if ($merchantId !== '' && (string) $request->input('merchant_id') !== $merchantId) {
            logger()->warning('Midtrans callback ignored: merchant mismatch.', ['order_id' => $orderId]);

            return response()->json(['status' => 'ignored', 'reason' => 'merchant_mismatch']);
        }

        $transactionStatus = strtolower((string) $request->input('transaction_status'));
        $fraudStatus = strtolower((string) $request->input('fraud_status', 'accept'));
        $paymentStatus = match ($transactionStatus) {
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

        if ($paymentStatus === null) {
            logger()->info('Midtrans callback ignored: unsupported transaction status.', [
                'order_id' => $orderId,
                'transaction_status' => $transactionStatus,
            ]);

            return response()->json(['status' => 'ignored', 'reason' => 'unsupported_status']);
        }

        $paymentTransitioned = DB::transaction(function () use ($order, $paymentStatus, $notification): bool {
            $lockedOrder = Order::query()->lockForUpdate()->find($order->id);
            if (! $lockedOrder) {
                return false;
            }

            // Never allow a delayed pending/failed notification to downgrade a paid order.
            if (in_array($lockedOrder->payment_status, ['refunded', 'partially_refunded'], true)) {
                return false;
            }

            if ($lockedOrder->payment_status === 'paid' && ! in_array($paymentStatus, ['refunded', 'partially_refunded'], true)) {
                return false;
            }

            // Late pending/challenge callbacks must not reopen a failed, cancelled,
            // or expired payment attempt. A verified settlement may still arrive late.
            if (in_array($lockedOrder->payment_status, ['failed', 'cancelled', 'expired'], true) &&
                in_array($paymentStatus, ['pending', 'challenge'], true)) {
                return false;
            }

            $lockedOrder->forceFill([
                'payment_status' => $paymentStatus,
                'transaction_id' => $notification['transaction_id'] ?? $lockedOrder->transaction_id,
                'payment_type' => $notification['payment_type'] ?? $lockedOrder->payment_type,
                'paid_at' => $paymentStatus === 'paid'
                    ? ($lockedOrder->paid_at ?? now())
                    : $lockedOrder->paid_at,
                'payment_payload' => $notification,
            ])->save();

            // A settlement may arrive after the customer cancelled an unpaid order.
            // Keep fulfillment cancelled and flag the late payment for admin refund handling.
            if ($lockedOrder->status === 'cancelled' && $paymentStatus === 'paid') {
                OrderCancellationRequest::query()->firstOrCreate(
                    ['order_id' => $lockedOrder->id],
                    [
                        'user_id' => $lockedOrder->user_id,
                        'reason' => 'other',
                        'description' => 'Pembayaran berhasil diterima setelah pesanan dibatalkan.',
                        'status' => 'approved',
                        'admin_note' => 'Pembayaran terlambat setelah pembatalan. Refund perlu diproses secara manual.',
                        'reviewed_at' => now(),
                    ],
                );
            }

            return true;
        });

        if ($paymentTransitioned && $paymentStatus === 'paid') {
            $paidOrder = $order->fresh()->load('orderItems');
            app(OrderNotificationService::class)->paymentSucceeded($paidOrder);
            app(OrderEmailService::class)->send($paidOrder, 'payment_successful');
        }

        // Payment state is separate from fulfillment state. Admin controls orders.status.
        return response()->json(['status' => 'processed']);
    }
}
