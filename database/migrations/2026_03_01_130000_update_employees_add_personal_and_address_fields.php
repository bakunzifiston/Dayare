<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'employee_code')) {
                $table->dropColumn('employee_code');
            }

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

