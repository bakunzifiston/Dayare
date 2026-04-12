<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supply_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processor_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('farmer_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('destination_facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->string('animal_type', 32);
            $table->unsignedInteger('quantity_requested');
            $table->date('preferred_date')->nullable();
            $table->string('status', 32)->default('pending');
            $table->foreignId('source_farm_id')->nullable()->constrained('farms')->nullOnDelete();
            $table->timestamps();

            $table->index(['farmer_id', 'status']);
            $table->index(['processor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_requests');
    }
};
