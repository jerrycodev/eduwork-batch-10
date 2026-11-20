<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderCancellationRequest;
use Illuminate\Support\Facades\DB;

class ExpireUnpaidOrder
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly OrderNotificationService $notifications,
    ) {}

    /**
     * Cancel an unpaid order after Midtrans confirms that its payment expired.
     * Returns true only when this call performed the cancellation.
     */
    public function handle(Order $order, string $paymentStatus = 'expired'): bool
    {
        $cancelled = DB::transaction(function () use ($order, $paymentStatus): bool {
            $lockedOrder = Order::query()->lockForUpdate()->find($order->id);

            if (
                ! $lockedOrder ||
                $lockedOrder->status !== 'pending' ||
                ! in_array($lockedOrder->payment_status, ['unpaid', 'pending', 'challenge', 'failed', 'expired'], true)
            ) {
                return false;
            }

            $lockedOrder->forceFill([
                'payment_status' => $paymentStatus,
                'status' => 'cancelled',
                'cancellation_reason' => 'payment_expired: Batas waktu reservasi pembayaran telah berakhir.',
                'auto_complete_at' => null,
            ])->save();

            if (! $lockedOrder->stock_restored_at) {
                foreach ($lockedOrder->orderItems()->get() as $item) {
                    $this->inventory->change(
                        productId: (string) $item->product_id,
                        variantId: $item->product_variant_id ? (string) $item->product_variant_id : null,
                        delta: (int) $item->quantity,
                        type: 'in',
                        referenceType: Order::class,
                        referenceId: (string) $lockedOrder->id,
                        notes: 'Stock restored after payment expired for order '.$lockedOrder->order_number,
                    );
                }

                $lockedOrder->forceFill(['stock_restored_at' => now()])->save();
            }

            if (! $lockedOrder->cancellationRequest()->exists()) {
                OrderCancellationRequest::query()->create([
                    'order_id' => $lockedOrder->id,
                    'user_id' => $lockedOrder->user_id,
                    'reason' => 'other',
                    'description' => 'Pembatalan otomatis karena pembayaran melewati batas waktu.',
                    'status' => 'completed',
                    'admin_note' => 'Dibatalkan otomatis setelah batas reservasi berakhir dan status transaksi dipastikan belum berhasil. Tidak ada refund karena pembayaran belum berhasil.',
                    'reviewed_at' => now(),
                    'resolved_at' => now(),
                ]);
            }

            return true;
        });

        if ($cancelled) {
            $this->notifications->paymentExpired($order->fresh());
        }

        return $cancelled;
    }
}
