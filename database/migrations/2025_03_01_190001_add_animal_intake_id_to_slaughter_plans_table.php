<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * SlaughterPlan must reference AnimalIntake. Compliance: cannot create slaughter without linked intake.
     */
    public function up(): void
    {
        Schema::table('slaughter_plans', function (Blueprint $table) {
            $table->foreignId('animal_intake_id')->nullable()->after('facility_id')->constrained('animal_intakes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('slaughter_plans', function (Blueprint $table) {
            $table->dropForeign(['animal_intake_id']);
        });
    }
};
