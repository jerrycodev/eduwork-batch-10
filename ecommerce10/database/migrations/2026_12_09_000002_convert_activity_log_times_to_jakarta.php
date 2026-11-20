<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->shiftActivityLogTimes('+7 hours');
    }

    public function down(): void
    {
        $this->shiftActivityLogTimes('-7 hours');
    }

    private function shiftActivityLogTimes(string $interval): void
    {
        $driver = DB::connection()->getDriverName();

        foreach (['admin_activity_logs', 'customer_activity_logs'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $expression = $driver === 'sqlite'
                ? "datetime(created_at, '{$interval}')"
                : $this->mysqlDateExpression($interval);

            DB::table($table)->update([
                'created_at' => DB::raw($expression),
            ]);
        }
    }

    private function mysqlDateExpression(string $interval): string
    {
        $hours = str_starts_with($interval, '-') ? -7 : 7;

        return "DATE_ADD(created_at, INTERVAL {$hours} HOUR)";
    }
};
