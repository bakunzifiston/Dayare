<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private const WORKFORCE_COUNT_COLUMNS = [
        'seasonal_workers',
        'full_time_employees',
        'workers_with_disabilities',
        'refugee_workers',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('businesses')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach (self::WORKFORCE_COUNT_COLUMNS as $column) {
            if (! Schema::hasColumn('businesses', $column)) {
                continue;
            }

            DB::statement(sprintf(
                'ALTER TABLE `businesses` MODIFY `%s` INT UNSIGNED NULL DEFAULT 0',
                $column
            ));
        }
    }

    public function down(): void
    {
        // Leave columns nullable with default 0.
    }
};
