<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('owner_national_id', 50)->nullable()->after('owner_last_name');
            $table->string('owner_emergency_contact', 255)->nullable()->after('owner_email');
        });

        Schema::table('farms', function (Blueprint $table) {
            $table->string('registration_number', 100)->nullable()->after('name');
            $table->decimal('gps_latitude', 10, 7)->nullable()->after('village_id');
            $table->decimal('gps_longitude', 10, 7)->nullable()->after('gps_latitude');
            $table->decimal('farm_size_hectares', 12, 2)->nullable()->after('gps_longitude');
            $table->string('land_ownership_type', 32)->nullable()->after('farm_size_hectares');
            $table->date('registration_date')->nullable()->after('land_ownership_type');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['owner_national_id', 'owner_emergency_contact']);
        });

        Schema::table('farms', function (Blueprint $table) {
            $table->dropColumn([
                'registration_number',
                'gps_latitude',
                'gps_longitude',
                'farm_size_hectares',
                'land_ownership_type',
                'registration_date',
            ]);
        });
    }
};
