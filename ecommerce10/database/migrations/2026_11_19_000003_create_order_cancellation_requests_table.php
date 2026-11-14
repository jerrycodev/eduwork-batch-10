<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('cancellation_reason')->nullable()->after('auto_complete_at');
            $table->timestamp('stock_restored_at')->nullable()->after('cancellation_reason');
        });

        Schema::create('order_cancellation_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 80);
            $table->text('description')->nullable();
            $table->string('status', 30)->default('requested')->index();
            $table->text('admin_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_cancellation_requests');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['cancellation_reason', 'stock_restored_at']);
        });
    }
};
