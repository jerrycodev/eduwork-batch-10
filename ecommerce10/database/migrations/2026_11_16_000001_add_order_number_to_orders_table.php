<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_number', 32)->nullable()->after('id');
        });

        DB::table('orders')->whereNull('order_number')->orderBy('id')->chunkById(200, function ($orders): void {
            foreach ($orders as $order) {
                do {
                    $date = $order->created_at
                        ? date('Ymd', strtotime($order->created_at))
                        : now()->format('Ymd');
                    $orderNumber = 'ORD-'.$date.'-'.Str::upper(Str::random(6));
                } while (DB::table('orders')->where('order_number', $orderNumber)->exists());

                DB::table('orders')->where('id', $order->id)->update(['order_number' => $orderNumber]);
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_number', 32)->nullable(false)->change();
            $table->unique('order_number');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['order_number']);
            $table->dropColumn('order_number');
        });
    }
};
