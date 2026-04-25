<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('operator_managers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('national_id')->unique();
            $table->string('phone_number');
            $table->string('email');
            $table->date('dob')->nullable();
            $table->string('nationality')->nullable();

            // Location Division IDs
            $table->foreignId('country_id')->nullable()->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('province_id')->nullable()->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('sector_id')->nullable()->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('cell_id')->nullable()->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('village_id')->nullable()->constrained('administrative_divisions')->nullOnDelete();

            // Legacy/Plain Text Location fields
            $table->string('country')->nullable();
            $table->string('district')->nullable();
            $table->string('sector')->nullable();
            $table->string('cell')->nullable();
            $table->string('village')->nullable();

            $table->string('status')->default('active'); // active, inactive

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operator_managers');
    }
};
