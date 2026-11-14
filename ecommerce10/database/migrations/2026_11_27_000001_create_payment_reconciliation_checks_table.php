<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_reconciliation_checks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('result', 20)->index();
            $table->string('local_status', 40)->nullable();
            $table->string('remote_status', 40)->nullable();
            $table->string('remote_order_id')->nullable();
            $table->string('remote_transaction_id')->nullable();
            $table->unsignedBigInteger('local_amount')->nullable();
            $table->decimal('remote_amount', 15, 2)->nullable();
            $table->text('message')->nullable();
            $table->timestamp('checked_at')->useCurrent()->index();
            $table->timestamps();

            $table->index(['order_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reconciliation_checks');
    }
};
