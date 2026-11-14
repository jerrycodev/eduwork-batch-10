<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_reconciliation_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->string('finding_code', 80);
            $table->string('fingerprint', 64);
            $table->timestamp('notified_at');
            $table->timestamps();
            $table->unique(['order_id', 'finding_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reconciliation_alerts');
    }
};
