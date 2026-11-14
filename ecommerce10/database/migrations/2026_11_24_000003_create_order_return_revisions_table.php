<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_return_revisions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_return_request_id')->constrained('order_return_requests')->cascadeOnDelete();
            $table->foreignUuid('requested_by')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->json('required_documents');
            $table->json('evidence_paths')->nullable();
            $table->json('evidence_original_names')->nullable();
            $table->text('customer_note')->nullable();
            $table->string('status', 30)->default('requested');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['order_return_request_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_return_revisions');
    }
};
