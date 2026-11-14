<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_review_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_review_id')->constrained('product_reviews')->cascadeOnDelete();
            $table->string('path');
            $table->string('media_type', 10);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['product_review_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_review_media');
    }
};
