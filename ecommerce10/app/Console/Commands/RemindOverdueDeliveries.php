<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderNotificationService;
use Illuminate\Console\Command;

class RemindOverdueDeliveries extends Command
{
    protected $signature = 'orders:remind-overdue-deliveries {--limit=200 : Maximum number of orders to notify}';

    protected $description = 'Notify administrators daily about shipped orders that passed their estimated delivery date.';

    /**
     * Menjalankan alur bisnis untuk menangani event atau permintaan yang diterima.
     */
    public function handle(OrderNotificationService $notifications): int
    {
        $orders = Order::query()
            ->where('status', 'shipped')
            ->whereNotNull('estimated_delivery_at')
            ->where('estimated_delivery_at', '<=', now())
            ->where(function ($query) {
                $query->whereNull('delivery_follow_up_last_notified_at')
                    ->orWhereDate('delivery_follow_up_last_notified_at', '<', today());
            })
            ->orderBy('estimated_delivery_at')
            ->limit(max(1, min((int) $this->option('limit'), 500)))
            ->get();

        $sent = 0;

        foreach ($orders as $order) {
            $updated = Order::query()
                ->whereKey($order->id)
                ->where('status', 'shipped')
                ->where(function ($query) {
                    $query->whereNull('delivery_follow_up_last_notified_at')
                        ->orWhereDate('delivery_follow_up_last_notified_at', '<', today());
                })
                ->update([
                    'delivery_follow_up_last_notified_at' => now(),
                    'delivery_follow_up_reminder_count' => (int) $order->delivery_follow_up_reminder_count + 1,
                    'updated_at' => now(),
                ]);

            if (! $updated) {
                continue;
            }

            $order->refresh();
            $notifications->deliveryFollowUp($order);
            $sent++;
        }

        $this->info("{$sent} pengingat keterlambatan pengiriman dikirim ke admin.");

        return self::SUCCESS;
    }
}
