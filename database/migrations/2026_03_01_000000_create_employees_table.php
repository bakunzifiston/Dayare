<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facility_id')->nullable()->constrained()->nullOnDelete();

            $table->string('employee_code')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('national_id')->nullable();
            $table->date('date_of_birth')->nullable();

            $table->string('work_email')->nullable();
            $table->string('personal_email')->nullable();
            $table->string('phone', 50)->nullable();

            $table->string('job_title')->nullable();
            $table->string('department')->nullable();
            $table->string('employment_type', 50)->default('full_time');
            $table->date('hire_date');
            $table->date('probation_end_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->string('status', 50)->default('active');

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};

