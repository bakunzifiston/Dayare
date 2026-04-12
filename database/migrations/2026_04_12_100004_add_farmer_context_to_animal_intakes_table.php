<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animal_intakes', function (Blueprint $table) {
            $table->foreignId('supply_request_id')->nullable()->after('facility_id')->constrained('supply_requests')->nullOnDelete();
            $table->foreignId('farm_id')->nullable()->after('supply_request_id')->constrained('farms')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('animal_intakes', function (Blueprint $table) {
            $table->dropForeign(['supply_request_id']);
            $table->dropForeign(['farm_id']);
            $table->dropColumn(['supply_request_id', 'farm_id']);
        });
    }
};
