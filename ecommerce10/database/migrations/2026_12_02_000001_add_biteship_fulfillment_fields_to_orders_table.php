<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('biteship_order_id')->nullable()->unique()->after('tracking_number');
            $table->string('biteship_tracking_id')->nullable()->after('biteship_order_id');
            $table->string('biteship_waybill_id')->nullable()->after('biteship_tracking_id');
            $table->string('biteship_status')->nullable()->after('biteship_waybill_id');
            $table->unsignedInteger('biteship_price')->nullable()->after('biteship_status');
            $table->timestamp('biteship_order_created_at')->nullable()->after('biteship_price');
            $table->timestamp('biteship_last_synced_at')->nullable()->after('biteship_order_created_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['biteship_order_id']);
            $table->dropColumn([
                'biteship_order_id', 'biteship_tracking_id', 'biteship_waybill_id',
                'biteship_status', 'biteship_price', 'biteship_order_created_at', 'biteship_last_synced_at',
            ]);
        });
    }
};
