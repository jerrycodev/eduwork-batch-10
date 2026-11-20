<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_cancellation_requests', function (Blueprint $table) {
            $table->string('refund_method', 20)->nullable()->after('refund_proof_original_name');
            $table->unsignedBigInteger('refund_amount')->nullable()->after('refund_method');
            $table->string('refund_reference', 150)->nullable()->after('refund_amount');
            $table->string('refund_request_status', 20)->nullable()->after('refund_reference');
            $table->timestamp('refund_requested_at')->nullable()->after('refund_request_status');
        });
    }

    public function down(): void
    {
        Schema::table('order_cancellation_requests', function (Blueprint $table) {
            $table->dropColumn(['refund_method', 'refund_amount', 'refund_reference', 'refund_request_status', 'refund_requested_at']);
        });
    }
};
