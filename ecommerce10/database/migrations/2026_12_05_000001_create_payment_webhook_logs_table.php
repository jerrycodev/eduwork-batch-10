<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_webhook_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 100)->nullable()->index();
            $table->string('payment_order_id')->nullable()->index();
            $table->string('transaction_id')->nullable()->index();
            $table->string('transaction_status', 50)->nullable()->index();
            $table->string('fraud_status', 40)->nullable();
            $table->string('mapped_status', 40)->nullable();
            $table->boolean('signature_valid')->nullable()->index();
            $table->string('result', 24)->default('received')->index();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('received_at')->useCurrent()->index();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_logs');
    }
};
