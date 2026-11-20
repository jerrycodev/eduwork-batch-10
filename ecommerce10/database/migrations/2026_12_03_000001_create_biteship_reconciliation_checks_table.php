<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biteship_reconciliation_checks', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('result', 24)->index();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('biteship_order_id')->nullable();
            $table->string('remote_status', 80)->nullable();
            $table->text('message')->nullable();
            $table->timestamp('checked_at')->useCurrent()->index();
            $table->timestamps();
            $table->index(['order_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biteship_reconciliation_checks');
    }
};
