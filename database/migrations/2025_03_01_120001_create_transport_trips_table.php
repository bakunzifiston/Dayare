<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * TransportTrip belongs to: Certificate, Batch, Origin Facility, Destination Facility.
     */
    public function up(): void
    {
        Schema::create('transport_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('origin_facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->foreignId('destination_facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->string('vehicle_plate_number', 50);
            $table->string('driver_name', 255);
            $table->string('driver_phone', 50)->nullable();
            $table->date('departure_date');
            $table->date('arrival_date')->nullable();
            $table->string('status', 50)->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_trips');
    }
};
