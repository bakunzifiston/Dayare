<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('business_type', 50)->nullable()->after('country');
            $table->foreignId('preferred_facility_id')->nullable()->after('registration_number')->constrained('facilities')->nullOnDelete();
            $table->string('preferred_species', 50)->nullable()->after('preferred_facility_id');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['preferred_facility_id']);
            $table->dropColumn(['business_type', 'preferred_facility_id', 'preferred_species']);
        });
    }
};
