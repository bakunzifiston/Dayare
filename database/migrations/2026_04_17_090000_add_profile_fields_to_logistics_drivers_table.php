<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_drivers', function (Blueprint $table) {
            $table->string('first_name', 100)->nullable()->after('company_id');
            $table->string('last_name', 100)->nullable()->after('first_name');
            $table->string('phone_number', 20)->nullable()->after('status');
            $table->string('national_id_or_license_id', 120)->nullable()->after('phone_number');
            $table->string('gender', 10)->nullable()->after('national_id_or_license_id');
            $table->date('dob')->nullable()->after('gender');
            $table->unsignedBigInteger('country_id')->nullable()->after('dob');
            $table->unsignedBigInteger('province_id')->nullable()->after('country_id');
            $table->unsignedBigInteger('district_id')->nullable()->after('province_id');
            $table->unsignedBigInteger('sector_id')->nullable()->after('district_id');
            $table->unsignedBigInteger('cell_id')->nullable()->after('sector_id');
            $table->unsignedBigInteger('village_id')->nullable()->after('cell_id');
            $table->string('photo_path')->nullable()->after('village_id');
            $table->string('license_category', 60)->nullable()->after('license_number');
            $table->unsignedTinyInteger('experience_years')->nullable()->after('license_category');

            $table->index(['company_id', 'first_name', 'last_name'], 'logistics_drivers_company_name_index');
        });
    }

    public function down(): void
    {
        Schema::table('logistics_drivers', function (Blueprint $table) {
            $table->dropIndex('logistics_drivers_company_name_index');

            $table->dropColumn([
                'first_name',
                'last_name',
                'phone_number',
                'national_id_or_license_id',
                'gender',
                'dob',
                'country_id',
                'province_id',
                'district_id',
                'sector_id',
                'cell_id',
                'village_id',
                'photo_path',
                'license_category',
                'experience_years',
            ]);
        });
    }
};
