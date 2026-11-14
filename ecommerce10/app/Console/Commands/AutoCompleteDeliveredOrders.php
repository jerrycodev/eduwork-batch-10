<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class AutoCompleteDeliveredOrders extends Command
{
    protected $signature = 'orders:auto-complete';

    protected $description = 'Automatically complete delivered orders after the confirmation window.';

    public function handle(): int
    {
        $completed = Order::query()
            ->where('status', 'delivered')
            ->whereNotNull('auto_complete_at')
            ->where('auto_complete_at', '<=', now())
            ->whereDoesntHave('returnRequest', fn ($query) => $query->whereIn('status', ['requested', 'approved']))
            ->update([
                'status' => 'completed',
                'completed_at' => now(),
                'auto_complete_at' => null,
                'updated_at' => now(),
            ]);

        $this->info("{$completed} pesanan otomatis diselesaikan.");

        return self::SUCCESS;
    }
}
