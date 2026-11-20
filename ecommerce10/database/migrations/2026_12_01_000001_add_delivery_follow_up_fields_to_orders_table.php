<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('estimated_delivery_at')->nullable()->index()->after('shipped_at');
            $table->timestamp('delivery_follow_up_last_notified_at')->nullable()->after('estimated_delivery_at');
            $table->unsignedInteger('delivery_follow_up_reminder_count')->default(0)->after('delivery_follow_up_last_notified_at');
        });

        DB::table('orders')
            ->where('status', 'shipped')
            ->whereNotNull('shipped_at')
            ->orderBy('id')
            ->chunkById(200, function ($orders): void {
                foreach ($orders as $order) {
                    preg_match_all('/\d+/', (string) $order->shipping_etd, $matches);
                    $days = collect($matches[0] ?? [])->map(fn ($value) => (int) $value)->max();

                    if ($days) {
                        DB::table('orders')->where('id', $order->id)->update([
                            'estimated_delivery_at' => Carbon::parse($order->shipped_at)->addDays($days),
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['estimated_delivery_at']);
            $table->dropColumn([
                'estimated_delivery_at',
                'delivery_follow_up_last_notified_at',
                'delivery_follow_up_reminder_count',
            ]);
        });
    }
};
