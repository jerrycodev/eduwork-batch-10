<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_shipments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('attempt')->default(1);
            $table->string('reference_id')->unique();
            $table->string('biteship_order_id')->nullable()->unique();
            $table->string('biteship_tracking_id')->nullable();
            $table->string('waybill_id')->nullable();
            $table->string('status', 80)->nullable();
            $table->unsignedInteger('price')->nullable();
            $table->timestamp('created_at_biteship')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'attempt']);
            $table->index(['order_id', 'status']);
        });

        DB::table('orders')->whereNotNull('biteship_order_id')->orderBy('id')->get()->each(function ($order): void {
            DB::table('order_shipments')->insert([
                'id' => (string) Illuminate\Support\Str::uuid(),
                'order_id' => $order->id,
                'attempt' => 1,
                'reference_id' => $order->order_number,
                'biteship_order_id' => $order->biteship_order_id,
                'biteship_tracking_id' => $order->biteship_tracking_id,
                'waybill_id' => $order->biteship_waybill_id ?: $order->tracking_number,
                'status' => $order->biteship_status,
                'price' => $order->biteship_price,
                'created_at_biteship' => $order->biteship_order_created_at,
                'last_synced_at' => $order->biteship_last_synced_at,
                'created_at' => $order->created_at ?? now(),
                'updated_at' => $order->updated_at ?? now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_shipments');
    }
};
