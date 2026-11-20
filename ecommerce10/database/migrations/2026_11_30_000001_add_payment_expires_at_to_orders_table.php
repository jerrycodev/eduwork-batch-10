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
            $table->timestamp('payment_expires_at')->nullable()->index()->after('payment_status');
        });

        // Existing pending orders receive the same reservation window from their creation time.
        DB::table('orders')
            ->where('status', 'pending')
            ->whereIn('payment_status', ['unpaid', 'pending', 'challenge'])
            ->orderBy('id')
            ->chunkById(200, function ($orders): void {
                foreach ($orders as $order) {
                    DB::table('orders')->where('id', $order->id)->update([
                        'payment_expires_at' => Carbon::parse($order->created_at)->addMinutes(30),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payment_expires_at');
        });
    }
};
