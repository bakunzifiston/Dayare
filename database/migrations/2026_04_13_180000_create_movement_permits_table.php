<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movement_permits', function (Blueprint $table) {
            $table->id();
            $table->string('permit_number', 100)->unique();
            $table->foreignId('farmer_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('source_farm_id')->constrained('farms')->cascadeOnDelete();
            $table->foreignId('destination_district_id')->nullable()->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('destination_sector_id')->nullable()->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('destination_cell_id')->nullable()->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('destination_village_id')->nullable()->constrained('administrative_divisions')->nullOnDelete();
            $table->string('transport_mode', 50)->nullable();
            $table->string('vehicle_plate', 50)->nullable();
            $table->date('issue_date');
            $table->date('expiry_date');
            $table->string('issued_by', 150);
            $table->string('file_path');
            $table->timestamps();

            $table->index(['farmer_id', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movement_permits');
    }
};

