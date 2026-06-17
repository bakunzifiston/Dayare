<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            if (! Schema::hasColumn('businesses', 'butchery_type')) {
                $table->string('butchery_type', 32)->nullable()->after('type');
            }
            if (! Schema::hasColumn('businesses', 'rfa_permit_number')) {
                $table->string('rfa_permit_number', 120)->nullable()->after('butchery_type');
            }
            if (! Schema::hasColumn('businesses', 'rfa_permit_expiry')) {
                $table->date('rfa_permit_expiry')->nullable()->after('rfa_permit_number');
            }
            if (! Schema::hasColumn('businesses', 'butcher_district')) {
                $table->string('butcher_district', 120)->nullable()->after('rfa_permit_expiry');
            }
            if (! Schema::hasColumn('businesses', 'butcher_sector')) {
                $table->string('butcher_sector', 120)->nullable()->after('butcher_district');
            }
            if (! Schema::hasColumn('businesses', 'butcher_cell')) {
                $table->string('butcher_cell', 120)->nullable()->after('butcher_sector');
            }
            if (! Schema::hasColumn('businesses', 'gps_lat')) {
                $table->decimal('gps_lat', 10, 7)->nullable()->after('butcher_cell');
            }
            if (! Schema::hasColumn('businesses', 'gps_lng')) {
                $table->decimal('gps_lng', 10, 7)->nullable()->after('gps_lat');
            }
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $columns = [
                'butchery_type',
                'rfa_permit_number',
                'rfa_permit_expiry',
                'butcher_district',
                'butcher_sector',
                'butcher_cell',
                'gps_lat',
                'gps_lng',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('businesses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
