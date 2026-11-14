<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_return_requests', function (Blueprint $table): void {
            $table->string('return_recipient')->nullable();
            $table->string('return_phone', 40)->nullable();
            $table->text('return_address')->nullable();
            $table->text('return_instructions')->nullable();
            $table->string('return_courier')->nullable();
            $table->string('return_tracking_number')->nullable();
            $table->timestamp('item_received_at')->nullable();
            $table->text('inspection_note')->nullable();
            $table->string('resolution_type', 30)->nullable();
            $table->string('resolution_status', 30)->default('pending');
            $table->unsignedBigInteger('refund_amount')->nullable();
            $table->string('refund_method', 30)->nullable();
            $table->string('refund_reference')->nullable();
            $table->string('refund_proof_path')->nullable();
            $table->string('refund_proof_original_name')->nullable();
            $table->string('replacement_courier')->nullable();
            $table->string('replacement_tracking_number')->nullable();
            $table->timestamp('replacement_shipped_at')->nullable();
            $table->timestamp('replacement_delivered_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('order_return_requests', function (Blueprint $table): void {
            $table->dropColumn([
                'return_recipient', 'return_phone', 'return_address', 'return_instructions',
                'return_courier', 'return_tracking_number', 'item_received_at', 'inspection_note',
                'resolution_type', 'resolution_status', 'refund_amount', 'refund_method',
                'refund_reference', 'refund_proof_path', 'refund_proof_original_name',
                'replacement_courier', 'replacement_tracking_number', 'replacement_shipped_at',
                'replacement_delivered_at',
            ]);
        });
    }
};
