<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('weight_gram')->default(1000)->after('stock');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('subtotal')->default(0)->after('total');
            $table->unsignedInteger('shipping_cost')->default(0)->after('subtotal');
            $table->string('shipping_courier')->nullable()->after('shipping_cost');
            $table->string('shipping_courier_code')->nullable()->after('shipping_courier');
            $table->string('shipping_service')->nullable()->after('shipping_courier_code');
            $table->string('shipping_service_code')->nullable()->after('shipping_service');
            $table->string('shipping_etd')->nullable()->after('shipping_service_code');
        });

        DB::table('orders')->where('subtotal', 0)->update(['subtotal' => DB::raw('total')]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'shipping_cost', 'shipping_courier', 'shipping_courier_code', 'shipping_service', 'shipping_service_code', 'shipping_etd']);
        });
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('weight_gram'));
    }
};
