<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('employees', 'employee_code')) {
            try {
                Schema::table('employees', function (Blueprint $table) {
                    $table->dropUnique(['employee_code']);
                });
            } catch (\Throwable) {
                // Index may already be missing on some databases.
            }

            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                DB::statement('DROP INDEX IF EXISTS employees_employee_code_unique');
            }

            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('employee_code');
            });
        }

        Schema::table('employees', function (Blueprint $table) {

            if (! Schema::hasColumn('employees', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('last_name');
            }
            if (! Schema::hasColumn('employees', 'nationality')) {
                $table->string('nationality', 100)->nullable()->after('date_of_birth');
            }

            if (! Schema::hasColumn('employees', 'country_id')) {
                $table->foreignId('country_id')->nullable()->after('nationality')
                    ->constrained('administrative_divisions')->nullOnDelete();
            }
            if (! Schema::hasColumn('employees', 'province_id')) {
                $table->foreignId('province_id')->nullable()
                    ->constrained('administrative_divisions')->nullOnDelete();
            }
            if (! Schema::hasColumn('employees', 'district_id')) {
                $table->foreignId('district_id')->nullable()
                    ->constrained('administrative_divisions')->nullOnDelete();
            }
            if (! Schema::hasColumn('employees', 'sector_id')) {
                $table->foreignId('sector_id')->nullable()
                    ->constrained('administrative_divisions')->nullOnDelete();
            }
            if (! Schema::hasColumn('employees', 'cell_id')) {
                $table->foreignId('cell_id')->nullable()
                    ->constrained('administrative_divisions')->nullOnDelete();
            }
            if (! Schema::hasColumn('employees', 'village_id')) {
                $table->foreignId('village_id')->nullable()
                    ->constrained('administrative_divisions')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('employee_code')->unique()->nullable()->after('facility_id');

            $table->dropColumn([
                'date_of_birth',
                'nationality',
                'country_id',
                'province_id',
                'district_id',
                'sector_id',
                'cell_id',
                'village_id',
            ]);
        });
    }
};

