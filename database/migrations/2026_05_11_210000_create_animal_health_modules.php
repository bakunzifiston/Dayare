<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vaccinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->string('vaccination_code', 64)->unique();
            $table->string('vaccine_name');
            $table->string('vaccine_type')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('batch_number')->nullable();
            $table->string('dosage')->nullable();
            $table->string('administration_method')->nullable();
            $table->date('vaccination_date');
            $table->date('next_due_date')->nullable();
            $table->string('veterinarian_name')->nullable();
            $table->string('veterinary_clinic')->nullable();
            $table->string('administered_by')->nullable();
            $table->string('status', 32)->default('scheduled');
            $table->text('side_effects')->nullable();
            $table->text('reaction_notes')->nullable();
            $table->string('attachment_path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['animal_id', 'vaccination_date']);
            $table->index(['status', 'next_due_date']);
        });

        Schema::create('treatments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->string('treatment_code', 64)->unique();
            $table->string('disease_name')->nullable();
            $table->text('symptoms')->nullable();
            $table->text('diagnosis')->nullable();
            $table->string('medicine_name')->nullable();
            $table->string('dosage')->nullable();
            $table->string('treatment_method')->nullable();
            $table->date('treatment_start_date');
            $table->date('treatment_end_date')->nullable();
            $table->string('veterinarian_name')->nullable();
            $table->string('response_to_treatment')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->string('status', 32)->default('ongoing');
            $table->text('notes')->nullable();
            $table->string('attachment_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['animal_id', 'treatment_start_date']);
            $table->index(['status', 'follow_up_date']);
        });

        Schema::create('disease_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->string('disease_code', 64)->unique();
            $table->string('disease_name');
            $table->text('symptoms')->nullable();
            $table->string('severity_level', 32)->default('medium');
            $table->date('diagnosis_date');
            $table->boolean('quarantine_required')->default(false);
            $table->string('contagious_status', 32)->default('unknown');
            $table->string('recovery_status', 32)->default('recovering');
            $table->string('veterinarian_name')->nullable();
            $table->text('notes')->nullable();
            $table->string('attachment_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['animal_id', 'diagnosis_date']);
            $table->index(['recovery_status', 'quarantine_required']);
        });

        Schema::create('veterinary_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->string('visit_code', 64)->unique();
            $table->date('visit_date');
            $table->string('veterinarian_name')->nullable();
            $table->string('clinic_name')->nullable();
            $table->string('purpose_of_visit')->nullable();
            $table->text('findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->boolean('follow_up_required')->default(false);
            $table->date('follow_up_date')->nullable();
            $table->string('attachment_path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['animal_id', 'visit_date']);
            $table->index(['follow_up_required', 'follow_up_date']);
        });

        Schema::create('mortality_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->string('mortality_code', 64)->unique();
            $table->date('death_date');
            $table->string('cause_of_death');
            $table->string('reported_by')->nullable();
            $table->boolean('postmortem_done')->default(false);
            $table->string('veterinarian_name')->nullable();
            $table->string('disposal_method')->nullable();
            $table->text('notes')->nullable();
            $table->string('attachment_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['animal_id', 'death_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mortality_records');
        Schema::dropIfExists('veterinary_visits');
        Schema::dropIfExists('disease_records');
        Schema::dropIfExists('treatments');
        Schema::dropIfExists('vaccinations');
    }
};
