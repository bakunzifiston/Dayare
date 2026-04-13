<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farmer_health_certificates', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_number', 120)->unique();
            $table->foreignId('farmer_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('farm_id')->constrained('farms')->cascadeOnDelete();
            $table->foreignId('livestock_id')->nullable()->constrained('livestock')->nullOnDelete();
            $table->string('batch_reference', 100)->nullable();
            $table->foreignId('source_health_record_id')->nullable()->constrained('animal_health_records')->nullOnDelete();
            $table->string('certificate_type', 50);
            $table->string('issued_by', 150);
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();
            $table->string('status', 30)->default('valid');
            $table->string('file_path');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['farmer_id', 'status']);
            $table->index(['farm_id', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmer_health_certificates');
    }
};

