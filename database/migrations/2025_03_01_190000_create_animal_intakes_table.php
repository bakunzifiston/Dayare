<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Animal Origin (Intake) – record where animals come from before slaughter. Must happen BEFORE SlaughterPlan.
     */
    public function up(): void
    {
        Schema::create('animal_intakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->date('intake_date');
            $table->string('supplier_firstname', 255);
            $table->string('supplier_lastname', 255);
            $table->string('supplier_contact', 100)->nullable();
            $table->string('farm_name', 255)->nullable();
            $table->string('farm_registration_number', 100)->nullable();
            $table->foreignId('country_id')->nullable()->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('province_id')->nullable()->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('sector_id')->nullable()->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('cell_id')->nullable()->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('village_id')->nullable()->constrained('administrative_divisions')->nullOnDelete();
            $table->string('species', 50);
            $table->unsignedInteger('number_of_animals');
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->decimal('total_price', 12, 2)->nullable();
            $table->text('animal_identification_numbers')->nullable();
            $table->string('transport_vehicle_plate', 50)->nullable();
            $table->string('driver_name', 255)->nullable();
            $table->string('animal_health_certificate_number', 100)->nullable();
            $table->date('health_certificate_issue_date')->nullable();
            $table->date('health_certificate_expiry_date')->nullable();
            $table->string('status', 50)->default('received');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_intakes');
    }
};
