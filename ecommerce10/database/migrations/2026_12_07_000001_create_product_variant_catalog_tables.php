<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedInteger('price')->change();
            $table->unsignedInteger('stock')->change();
            $table->softDeletes();
            $table->string('sku', 100)->nullable()->unique();
            $table->string('barcode', 100)->nullable()->unique();
            $table->unsignedInteger('compare_at_price')->nullable();
            $table->unsignedInteger('cost_price')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->decimal('length_cm', 8, 2)->nullable();
            $table->decimal('width_cm', 8, 2)->nullable();
            $table->decimal('height_cm', 8, 2)->nullable();
        });
        Schema::table('products', function (Blueprint $table): void {
            $table->text('description')->change();
        });

        Schema::create('product_variants', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 100)->unique();
            $table->string('barcode', 100)->nullable()->unique();
            $table->string('name')->nullable();
            $table->unsignedInteger('price');
            $table->unsignedInteger('compare_at_price')->nullable();
            $table->unsignedInteger('cost_price')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('weight_gram')->default(1000);
            $table->decimal('length_cm', 8, 2)->nullable();
            $table->decimal('width_cm', 8, 2)->nullable();
            $table->decimal('height_cm', 8, 2)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['product_id', 'is_active']);
        });

        Schema::create('product_options', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('code', 80);
            $table->string('display_type', 20)->default('button');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'code']);
        });

        Schema::create('product_option_values', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_option_id')->constrained()->cascadeOnDelete();
            $table->string('value', 100);
            $table->string('label', 100);
            $table->string('color_hex', 7)->nullable();
            $table->string('swatch_image')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['product_option_id', 'value']);
        });

        Schema::create('product_variant_option_values', function (Blueprint $table): void {
            $table->foreignUuid('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_option_value_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_variant_id', 'product_option_value_id'], 'variant_option_value_primary');
        });

        Schema::table('product_images', function (Blueprint $table): void {
            $table->foreignUuid('product_variant_id')->nullable()->after('product_id')->constrained('product_variants')->nullOnDelete();
            $table->index(['product_variant_id', 'sort_order']);
        });

        Schema::table('carts', function (Blueprint $table): void {
            $table->foreignUuid('product_variant_id')->nullable()->after('product_id')->constrained('product_variants')->nullOnDelete();
        });
        Schema::table('order_items', function (Blueprint $table): void {
            $table->foreignUuid('product_variant_id')->nullable()->after('product_id')->constrained('product_variants')->nullOnDelete();
            $table->string('product_sku', 100)->nullable()->after('product_name');
            $table->string('product_variant_name')->nullable()->after('product_sku');
        });
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->foreignUuid('product_variant_id')->nullable()->after('product_id')->constrained('product_variants')->nullOnDelete();
            $table->index(['product_variant_id', 'created_at']);
        });

        DB::table('products')->orderBy('id')->chunk(200, function ($products): void {
            foreach ($products as $product) {
                $sku = 'PRD-'.strtoupper(str_replace('-', '', (string) $product->id));
                DB::table('products')->where('id', $product->id)->update(['sku' => $sku]);
                $variantId = (string) Str::uuid();
                DB::table('product_variants')->insert([
                    'id' => $variantId,
                    'product_id' => $product->id,
                    'sku' => $sku,
                    'name' => 'Default',
                    'price' => $product->price,
                    'stock' => $product->stock,
                    'weight_gram' => $product->weight_gram ?? 1000,
                    'is_default' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('carts')->where('product_id', $product->id)->whereNull('product_variant_id')->update(['product_variant_id' => $variantId]);
                DB::table('order_items')->where('product_id', $product->id)->whereNull('product_variant_id')->update([
                    'product_variant_id' => $variantId,
                    'product_sku' => $sku,
                    'product_variant_name' => 'Default',
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', fn (Blueprint $table) => $table->dropConstrainedForeignId('product_variant_id'));
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('product_variant_id');
            $table->dropColumn(['product_sku', 'product_variant_name']);
        });
        Schema::table('carts', fn (Blueprint $table) => $table->dropConstrainedForeignId('product_variant_id'));
        Schema::table('product_images', fn (Blueprint $table) => $table->dropConstrainedForeignId('product_variant_id'));
        Schema::dropIfExists('product_variant_option_values');
        Schema::dropIfExists('product_option_values');
        Schema::dropIfExists('product_options');
        Schema::dropIfExists('product_variants');
        Schema::table('products', function (Blueprint $table): void {
            $table->integer('price')->change();
            $table->integer('stock')->change();
            $table->dropSoftDeletes();
            $table->dropUnique(['sku']);
            $table->dropUnique(['barcode']);
            $table->dropColumn(['sku', 'barcode', 'compare_at_price', 'cost_price', 'is_active', 'is_featured', 'meta_title', 'meta_description', 'length_cm', 'width_cm', 'height_cm']);
        });
        Schema::table('products', function (Blueprint $table): void {
            $table->string('description')->change();
        });
    }
};
