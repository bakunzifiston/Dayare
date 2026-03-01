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
        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('facility_name');
            $table->string('facility_type'); // Slaughterhouse, Butchery, etc.
            $table->string('district');
            $table->string('sector');
            $table->string('gps')->nullable(); // GPS coordinates
            $table->string('license_number')->nullable();
            $table->date('license_issue_date')->nullable();
            $table->date('license_expiry_date')->nullable();
            $table->unsignedInteger('daily_capacity')->nullable(); // e.g. animals per day or kg
            $table->string('status')->default('active'); // active, suspended
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};
