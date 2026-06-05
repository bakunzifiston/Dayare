<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('transport_trips', 'destination_name')) {
            Schema::table('transport_trips', function (Blueprint $table) {
                $table->string('destination_name', 255)->nullable()->after('destination_facility_id');
                $table->string('destination_country', 100)->nullable()->after('destination_name');
                $table->text('destination_address')->nullable()->after('destination_country');
            });
        }

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            Schema::table('transport_trips', function (Blueprint $table) {
                $table->dropForeign(['destination_facility_id']);
            });
            DB::statement('ALTER TABLE transport_trips MODIFY destination_facility_id BIGINT UNSIGNED NULL');
            Schema::table('transport_trips', function (Blueprint $table) {
                $table->foreign('destination_facility_id')->references('id')->on('facilities')->nullOnDelete();
            });
        } elseif ($driver === 'sqlite') {
            Schema::table('transport_trips', function (Blueprint $table) {
                $table->foreignId('destination_facility_id')->nullable()->change();
            });
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE transport_trips ALTER COLUMN destination_facility_id DROP NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::table('transport_trips', function (Blueprint $table) {
            $table->dropColumn(['destination_name', 'destination_country', 'destination_address']);
        });
    }
};
