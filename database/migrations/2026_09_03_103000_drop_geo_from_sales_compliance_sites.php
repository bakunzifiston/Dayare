<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_compliance_sites', function (Blueprint $table) {
            if (Schema::hasColumn('sales_compliance_sites', 'latitude')) {
                $table->dropColumn('latitude');
            }
            if (Schema::hasColumn('sales_compliance_sites', 'longitude')) {
                $table->dropColumn('longitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_compliance_sites', function (Blueprint $table) {
            if (! Schema::hasColumn('sales_compliance_sites', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable();
            }
            if (! Schema::hasColumn('sales_compliance_sites', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable();
            }
        });
    }
};
