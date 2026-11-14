<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_status_resolutions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason');
            $table->string('previous_status', 40);
            $table->string('new_status', 40);
            $table->string('remote_status', 40);
            $table->string('remote_order_id');
            $table->string('remote_transaction_id')->nullable();
            $table->decimal('remote_amount', 15, 2);
            $table->timestamp('resolved_at')->useCurrent();
            $table->timestamps();
            $table->index(['order_id', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_status_resolutions');
    }
};
