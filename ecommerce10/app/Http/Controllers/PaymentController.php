<?php

namespace App\Http\Controllers;

use App\Models\CustomerActivityLog;
use App\Models\Order;
use App\Models\OrderCancellationRequest;
use App\Models\PaymentWebhookLog;
use App\Services\ExpireUnpaidOrder;
use App\Services\MidtransPaymentService;
use App\Services\OrderEmailService;
use App\Services\OrderNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class PaymentController extends Controller
{
    public function __construct(private readonly MidtransPaymentService $payments) {}

    public function checkout(): RedirectResponse
    {
        return redirect()->route('cart')
            ->with('info', 'Silakan lanjutkan checkout melalui keranjang belanja.');
    }

    public function success(Request $request)
    {
        $order = $this->resolveReturnOrder($request);

        return view('payments.success', [
            'order' => $order,
            'verified' => $order?->payment_status === 'paid',
        ]);
    }

    /**
     * Mencatat kegagalan proses beserta informasi error untuk investigasi.
     */
    public function failed(Request $request)
    {
        $order = $this->resolveReturnOrder($request);
        $isPaid = $order?->payment_status === 'paid';

        return view('payments.failed', [
            'order' => $order,
            'isPaid' => $isPaid,
            'isPending' => $order && in_array($order->payment_status, ['pending', 'challenge'], true),
        ]);
    }

    private function resolveReturnOrder(Request $request): ?Order
    {
        $paymentOrderId = trim((string) $request->query('order_id', ''));

        if ($paymentOrderId === '') {
            return null;
        }

        return Order::query()
            ->where('payment_order_id', $paymentOrderId)
            ->first();
    }

    /**
     * Menyiapkan halaman pembuatan data baru.
     */
    public function create(Request $request, Order $order): RedirectResponse
    {
        $request->attributes->set('customer_activity_subject', $order);
        $request->attributes->set('customer_activity_metadata', ['order_number' => $order->order_number, 'total' => (int) $order->total, 'currency' => 'IDR', 'payment_status' => $order->payment_status, 'payment_provider' => 'Midtrans']);
        abort_unless((string) $order->user_id === (string) auth()->id(), 403);

        if ($order->payment_expires_at && $order->payment_expires_at->isPast()) {
            return redirect()->route('orders.customer.show', $order)
                ->with('error', 'Batas waktu pembayaran telah berakhir. Pesanan sedang menunggu pembatalan otomatis.');
        }

        if (in_array($order->payment_status, ['paid', 'refunded'], true) || $order->status === 'cancelled') {
            return redirect()->route('orders.customer.show', $order)
                ->with('success', 'Pembayaran pesanan ini sudah berhasil.');
        }

        try {
            $this->payments->createPayment($order->load('orderItems'));

            return redirect()->route('orders.customer.show', $order)
                ->with('success', 'Halaman pembayaran berhasil disiapkan.');
        } catch (Throwable $exception) {
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
        $transactionStatus = strtolower((string) $request->input('transaction_status'));
        $fraudStatus = strtolower((string) $request->input('fraud_status', 'accept'));
        $webhookLog = PaymentWebhookLog::query()->create([
            'event' => $transactionStatus !== '' ? 'transaction.'.$transactionStatus : ((string) $request->input('notification_type', 'midtrans.notification')),
            'payment_order_id' => $orderId ?: null,
            'transaction_id' => $request->input('transaction_id'),
            'transaction_status' => $transactionStatus ?: null,
            'fraud_status' => $fraudStatus ?: null,
            'result' => 'received',
            'message' => 'Notifikasi Midtrans diterima; menunggu validasi.',
            'payload' => $this->safeMidtransPayload($request->all()),
            'received_at' => now(),
        ]);

        if ($serverKey === '' || $orderId === '' || $statusCode === '' || $grossAmount === '' || $signatureKey === '') {
            logger()->warning('Midtrans callback ignored: incomplete notification payload.');

            return $this->finishWebhook($webhookLog, 'rejected', 'Payload notifikasi tidak lengkap atau konfigurasi server key belum tersedia.', null, null, 'incomplete_payload');
        }

        $expectedSignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        if (! hash_equals($expectedSignature, $signatureKey)) {
            logger()->warning('Midtrans callback ignored: signature verification failed.', ['order_id' => $orderId]);

            return $this->finishWebhook($webhookLog, 'rejected', 'Signature Midtrans tidak valid.', false, null, 'invalid_signature');
        }
        $webhookLog->update(['signature_valid' => true]);

        $notification = $request->all();
        $order = Order::query()->where('payment_order_id', $orderId)->first();

        if (! $order) {
            logger()->info('Midtrans callback received for unknown order.', ['order_id' => $orderId]);

            return $this->finishWebhook($webhookLog, 'rejected', 'Order pembayaran tidak ditemukan di RevStore.', true, null, 'order_not_found');
        }
        $webhookLog->update(['order_id' => $order->id]);

        if (! preg_match('/^\\d+(?:\\.\\d{1,2})?$/D', $grossAmount)) {
            logger()->warning('Midtrans callback ignored: invalid gross amount format.', ['order_id' => $orderId]);

            return $this->finishWebhook($webhookLog, 'rejected', 'Format gross_amount tidak valid.', true, $order, 'amount_mismatch');
        }
        [$wholeAmount, $fractionalAmount] = array_pad(explode('.', $grossAmount, 2), 2, '0');
        $grossAmountInMinorUnits = ((int) $wholeAmount * 100) + (int) str_pad($fractionalAmount, 2, '0');
        if ($grossAmountInMinorUnits !== ((int) $order->total * 100)) {
            logger()->warning('Midtrans callback ignored: gross amount mismatch.', ['order_id' => $orderId]);

            return $this->finishWebhook($webhookLog, 'rejected', 'Nominal Midtrans tidak cocok dengan total pesanan RevStore.', true, $order, 'amount_mismatch');
        }

        $merchantId = (string) config('midtrans.merchant_id');
        if ($merchantId !== '' && (string) $request->input('merchant_id') !== $merchantId) {
            logger()->warning('Midtrans callback ignored: merchant mismatch.', ['order_id' => $orderId]);

            return $this->finishWebhook($webhookLog, 'rejected', 'Merchant ID notifikasi tidak cocok dengan konfigurasi RevStore.', true, $order, 'merchant_mismatch');
        }

        $mappedStatus = null;
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

            return $this->finishWebhook($webhookLog, 'rejected', 'Status transaksi Midtrans belum didukung.', true, $order, 'unsupported_status');
        }
        $webhookLog->update(['mapped_status' => $paymentStatus]);

        try {
            $paymentTransitioned = DB::transaction(function () use ($order, $paymentStatus, $notification): bool {
                $lockedOrder = Order::query()->lockForUpdate()->find($order->id);
                if (! $lockedOrder) {
                    return false;
                }

                // Never allow a delayed pending/failed notification to downgrade a paid order.
                if (in_array($lockedOrder->payment_status, ['refunded', 'partially_refunded'], true)) {
                    // Refund callbacks can be retried or report a larger cumulative partial
                    // refund. Keep the most advanced state and never emit payment-success side effects.
                    if (in_array($paymentStatus, ['refunded', 'partially_refunded'], true)) {
                        $existingRefund = (int) round((float) data_get($lockedOrder->payment_payload, 'refund_amount', 0));
                        $incomingRefund = (int) round((float) ($notification['refund_amount'] ?? 0));
                        if ($incomingRefund >= $existingRefund && $incomingRefund <= (int) $lockedOrder->total) {
                            $lockedOrder->forceFill([
                                'payment_status' => $paymentStatus === 'refunded' || $lockedOrder->payment_status === 'refunded'
                                    ? 'refunded'
                                    : 'partially_refunded',
                                'payment_payload' => $notification,
                            ])->save();
                        }
                    }

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
                    OrderCancellationRequest::query()->updateOrCreate(
                        ['order_id' => $lockedOrder->id],
                        [
                            'user_id' => $lockedOrder->user_id,
                            'reason' => 'other',
                            'description' => 'Pembayaran berhasil diterima setelah pesanan dibatalkan.',
                            'status' => 'approved',
                            'admin_note' => 'Pembayaran terlambat setelah pembatalan. Refund perlu diproses secara manual.',
                            'reviewed_at' => now(),
                            'resolved_at' => null,
                        ],
                    );
                }

                return true;
            });

            if ($paymentTransitioned) {
                $customer = $order->user;
                if ($customer && $customer->role === 'customer') {
                    try {
                        CustomerActivityLog::query()->create([
                            'user_id' => $customer->id,
                            'actor_name' => $customer->name,
                            'actor_email' => $customer->email,
                            'action' => 'payment.'.$paymentStatus,
                            'subject_type' => Order::class,
                            'subject_id' => (string) $order->id,
                            'route_name' => 'midtrans.callback',
                            'method' => 'POST',
                            'response_status' => 200,
                            'ip_address' => null,
                            'user_agent' => null,
                            'metadata' => [
                                'source' => 'midtrans_webhook',
                                'order_number' => $order->order_number,
                                'payment_status' => $paymentStatus,
                                'transaction_id' => $notification['transaction_id'] ?? null,
                                'payment_type' => $notification['payment_type'] ?? null,
                                'amount' => (int) $order->total,
                                'currency' => 'IDR',
                            ],
                            'created_at' => now(),
                        ]);
                    } catch (Throwable $exception) {
                        report($exception);
                    }
                }
            }

            if ($paymentTransitioned && $paymentStatus === 'paid') {
                $paidOrder = $order->fresh()->load('orderItems');
                app(OrderNotificationService::class)->paymentSucceeded($paidOrder);
                app(OrderEmailService::class)->send($paidOrder, 'payment_successful');
            }

            if ($paymentStatus === 'expired') {
                app(ExpireUnpaidOrder::class)->handle($order->fresh());
            }

            // Payment state is separate from fulfillment state. Admin controls orders.status.
            return $this->finishWebhook($webhookLog, 'processed', 'Notifikasi Midtrans berhasil diproses. Status pembayaran RevStore: '.$order->fresh()->payment_status.'.', true, $order, 'processed');
        } catch (Throwable $exception) {
            report($exception);
            logger()->error('Midtrans webhook processing failed.', [
                'webhook_log_id' => $webhookLog->id,
                'order_id' => $order->id,
                'exception' => $exception->getMessage(),
            ]);

            return $this->finishWebhook($webhookLog, 'failed', mb_substr($exception->getMessage(), 0, 4000), true, $order, 'processing_failed', 500);
        }
    }

    private function finishWebhook(
        PaymentWebhookLog $log,
        string $result,
        string $message,
        ?bool $signatureValid,
        ?Order $order,
        string $reason,
        int $httpStatus = 200,
    ): JsonResponse {
        $log->update([
            'order_id' => $order?->id ?? $log->order_id,
            'signature_valid' => $signatureValid,
            'result' => $result,
            'http_status' => $httpStatus,
            'message' => $message,
            'processed_at' => now(),
        ]);

        return response()->json([
            'status' => $result === 'processed' ? 'processed' : ($result === 'failed' ? 'failed' : 'ignored'),
            'reason' => $reason,
        ], $httpStatus);
    }

    /** Store only payment notification fields needed for audit; never store signature_key or card data. */
    private function safeMidtransPayload(array $payload): array
    {
        $allowed = [
            'order_id', 'status_code', 'gross_amount', 'transaction_status', 'transaction_id',
            'payment_type', 'fraud_status', 'merchant_id', 'transaction_time', 'settlement_time',
            'expiry_time', 'notification_type', 'refund_amount', 'refund_key', 'currency',
            'bank', 'store', 'payment_code', 'issuer', 'reason', 'approval_code',
        ];

        return array_intersect_key($payload, array_flip($allowed));
    }
}
