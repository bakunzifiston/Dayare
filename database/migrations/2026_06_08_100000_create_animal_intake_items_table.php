<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Individual animals received in one intake session.
     * Parent animal_intakes row is the session header (source, facility, compliance docs).
     */
    public function up(): void
    {
        Schema::create('animal_intake_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_intake_id')->constrained('animal_intakes')->cascadeOnDelete();
            $table->string('ear_tag', 100);
            $table->string('species', 50);
            $table->string('sex', 20);
            $table->unsignedSmallInteger('age_months')->nullable();
            $table->decimal('live_weight_kg', 8, 2)->nullable();
            $table->string('body_condition_score', 20)->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->string('health_status', 30)->default('healthy');
            $table->text('notes')->nullable();
            $table->foreignId('slaughter_plan_id')->nullable()->constrained('slaughter_plans')->nullOnDelete();
            $table->timestamps();

            $table->unique('ear_tag');
            $table->index(['animal_intake_id', 'species']);
            $table->index(['animal_intake_id', 'health_status']);
            $table->index('slaughter_plan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_intake_items');
    }
};
