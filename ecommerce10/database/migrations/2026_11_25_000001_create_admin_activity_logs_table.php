<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_name');
            $table->string('actor_email');
            $table->string('action', 120)->index();
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->string('route_name')->nullable();
            $table->string('method', 10);
            $table->unsignedSmallInteger('response_status');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_activity_logs');
    }
};
