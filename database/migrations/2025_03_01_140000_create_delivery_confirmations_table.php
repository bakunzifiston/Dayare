<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * TransportTrip (1) → One Delivery Confirmation.
     */
    public function up(): void
    {
        Schema::create('delivery_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transport_trip_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('receiving_facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->unsignedInteger('received_quantity')->default(0);
            $table->date('received_date');
            $table->string('receiver_name', 255);
            $table->string('confirmation_status', 50)->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_confirmations');
    }
};
