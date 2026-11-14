<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->string('district')->nullable()->after('city');
            $table->string('village')->nullable()->after('district');
            $table->string('province_code', 2)->nullable()->after('province');
            $table->string('regency_code', 5)->nullable()->after('province_code');
            $table->string('district_code', 8)->nullable()->after('regency_code');
            $table->string('village_code', 13)->nullable()->after('district_code');
            $table->index('village_code');
        });
    }

    public function down(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->dropIndex(['village_code']);
            $table->dropColumn([
                'district', 'village', 'province_code', 'regency_code', 'district_code', 'village_code',
            ]);
        });
    }
};
