<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 50);
            $table->string('recipient_name');
            $table->string('phone', 30);
            $table->text('address_line');
            $table->string('city');
            $table->string('province');
            $table->string('postal_code', 15);
            $table->string('country')->default('Indonesia');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'is_default']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('recipient_name')->nullable()->after('user_email');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('recipient_name');
        });

        Schema::dropIfExists('user_addresses');
    }
};
