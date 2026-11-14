<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_status')->default('unpaid')->after('status');
            $table->string('payment_order_id')->nullable()->unique()->after('payment_status');
            $table->string('snap_token')->nullable()->after('payment_order_id');
            $table->text('snap_redirect_url')->nullable()->after('snap_token');
            $table->string('transaction_id')->nullable()->index()->after('snap_redirect_url');
            $table->string('payment_type')->nullable()->after('transaction_id');
            $table->timestamp('paid_at')->nullable()->after('payment_type');
            $table->json('payment_payload')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['payment_order_id']);
            $table->dropIndex(['transaction_id']);
            $table->dropColumn([
                'payment_status', 'payment_order_id', 'snap_token', 'snap_redirect_url',
                'transaction_id', 'payment_type', 'paid_at', 'payment_payload',
            ]);
        });
    }
};
