<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('stock_before');
            $table->unsignedInteger('stock_after');
            $table->string('reference_type')->nullable();
            $table->uuid('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
            $table->index(['product_id', 'created_at']);
        });

        // Preserve the current balances as opening stock; historical movements before this
        // feature cannot be reconstructed reliably from the products table alone.
        DB::table('products')->where('stock', '>', 0)->orderBy('id')->chunk(500, function ($products): void {
            $timestamp = now();
            DB::table('stock_movements')->insert($products->map(fn ($product) => [
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => $product->stock,
                'stock_before' => 0,
                'stock_after' => $product->stock,
                'notes' => 'Opening stock balance (inventory migration baseline)',
                'created_by' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])->all());
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
