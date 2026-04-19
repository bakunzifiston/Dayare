<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_vehicles', function (Blueprint $table) {
            $table->decimal('capacity_value', 12, 2)->nullable()->after('type');
            $table->string('capacity_unit', 30)->nullable()->after('capacity_value');
            $table->json('vehicle_features')->nullable()->after('capacity_unit');
        });
    }

    public function down(): void
    {
        Schema::table('logistics_vehicles', function (Blueprint $table) {
            $table->dropColumn(['capacity_value', 'capacity_unit', 'vehicle_features']);
        });
    }
};
