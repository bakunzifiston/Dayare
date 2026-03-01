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
        Schema::create('inspectors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('national_id')->unique();
            $table->string('phone_number');
            $table->string('email');
            $table->date('dob');
            $table->string('nationality');

            // Location (Country, District, Sector, Cell, Village)
            $table->string('country');
            $table->string('district');
            $table->string('sector');
            $table->string('cell')->nullable();
            $table->string('village')->nullable();

            $table->string('authorization_number');
            $table->date('authorization_issue_date');
            $table->date('authorization_expiry_date');
            $table->string('species_allowed'); // e.g. "Cattle, Goat, Sheep" or JSON
            $table->unsignedInteger('daily_capacity')->nullable();
            $table->string('stamp_serial_number')->nullable();

            $table->string('status')->default('active'); // active, expired

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspectors');
    }
};
