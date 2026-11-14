<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_cancellation_requests', function (Blueprint $table) {
            $table->string('refund_proof_path')->nullable()->after('admin_note');
            $table->string('refund_proof_original_name')->nullable()->after('refund_proof_path');
        });
    }

    public function down(): void
    {
        Schema::table('order_cancellation_requests', function (Blueprint $table) {
            $table->dropColumn(['refund_proof_path', 'refund_proof_original_name']);
        });
    }
};
