<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('tracking_number')->nullable()->after('shipping_etd');
            $table->timestamp('shipped_at')->nullable()->after('tracking_number');
            $table->timestamp('delivered_at')->nullable()->after('shipped_at');
            $table->timestamp('confirmed_at')->nullable()->after('delivered_at');
            $table->timestamp('completed_at')->nullable()->after('confirmed_at');
            $table->timestamp('auto_complete_at')->nullable()->index()->after('completed_at');
        });

        // Existing success orders were already treated as finished by the previous workflow.
        DB::table('orders')->where('status', 'success')->update([
            'status' => 'completed',
            'completed_at' => DB::raw('updated_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['auto_complete_at']);
            $table->dropColumn(['tracking_number', 'shipped_at', 'delivered_at', 'confirmed_at', 'completed_at', 'auto_complete_at']);
        });
    }
};
