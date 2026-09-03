<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_compliance_sites', function (Blueprint $table) {
            if (! Schema::hasColumn('sales_compliance_sites', 'country_id')) {
                $table->foreignId('country_id')->nullable()->constrained('administrative_divisions')->nullOnDelete();
            }
            if (! Schema::hasColumn('sales_compliance_sites', 'province_id')) {
                $table->foreignId('province_id')->nullable()->constrained('administrative_divisions')->nullOnDelete();
            }
            if (! Schema::hasColumn('sales_compliance_sites', 'district_id')) {
                $table->foreignId('district_id')->nullable()->constrained('administrative_divisions')->nullOnDelete();
            }
            if (! Schema::hasColumn('sales_compliance_sites', 'sector_id')) {
                $table->foreignId('sector_id')->nullable()->constrained('administrative_divisions')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_compliance_sites', function (Blueprint $table) {
            if (Schema::hasColumn('sales_compliance_sites', 'sector_id')) {
                $table->dropConstrainedForeignId('sector_id');
            }
            if (Schema::hasColumn('sales_compliance_sites', 'district_id')) {
                $table->dropConstrainedForeignId('district_id');
            }
            if (Schema::hasColumn('sales_compliance_sites', 'province_id')) {
                $table->dropConstrainedForeignId('province_id');
            }
            if (Schema::hasColumn('sales_compliance_sites', 'country_id')) {
                $table->dropConstrainedForeignId('country_id');
            }
        });
    }
};
