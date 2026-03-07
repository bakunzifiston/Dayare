<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('contract_category', 20)->default('supplier')->after('business_id'); // 'employee' | 'supplier'
            $table->foreignId('employee_id')->nullable()->after('facility_id')->constrained()->nullOnDelete();
            $table->text('description')->nullable()->after('title');
            $table->date('renewal_date')->nullable()->after('end_date');
            $table->string('termination_reason')->nullable()->after('status');
            $table->foreignId('contract_owner_id')->nullable()->after('notes')->constrained('users')->nullOnDelete();

            // Employee contract fields
            $table->string('job_position')->nullable()->after('contract_owner_id');
            $table->string('department')->nullable();
            $table->string('supervisor_name')->nullable();
            $table->string('employment_type', 50)->nullable(); // full_time, part_time, contract
            $table->string('work_schedule')->nullable();
            $table->text('salary_payment_terms')->nullable();
            $table->string('working_hours')->nullable();
            $table->string('probation_period')->nullable();
            $table->string('medical_certificate_number')->nullable();
            $table->date('medical_certificate_expiry_date')->nullable();
            $table->string('hygiene_training_status')->nullable();
            $table->date('safety_training_date')->nullable();
            $table->text('certification_requirements')->nullable();
            $table->string('signed_contract_file')->nullable();
            $table->text('supporting_documents')->nullable();

            // Supplier contract fields (supplier_id and facility_id already exist)
            $table->string('farm_name')->nullable();
            $table->string('farm_registration_number')->nullable();
            $table->string('supplier_contact_person')->nullable();
            $table->string('supplier_phone')->nullable();
            $table->string('supplier_email')->nullable();
            $table->string('location_district')->nullable();
            $table->string('location_sector')->nullable();
            $table->string('species_covered')->nullable(); // e.g. Cattle, Goat, Sheep, Poultry
            $table->unsignedInteger('estimated_quantity')->nullable();
            $table->string('delivery_frequency', 50)->nullable(); // daily, weekly, monthly
            $table->text('animal_health_cert_requirement')->nullable();
            $table->text('veterinary_inspection_requirement')->nullable();
            $table->text('animal_welfare_compliance')->nullable();
            $table->string('transport_responsibility', 50)->nullable(); // supplier, facility
            $table->string('vehicle_plate')->nullable();
            $table->string('driver_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropForeign(['contract_owner_id']);
        });
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'contract_category', 'employee_id', 'description', 'renewal_date', 'termination_reason', 'contract_owner_id',
                'job_position', 'department', 'supervisor_name', 'employment_type', 'work_schedule', 'salary_payment_terms',
                'working_hours', 'probation_period', 'medical_certificate_number', 'medical_certificate_expiry_date',
                'hygiene_training_status', 'safety_training_date', 'certification_requirements', 'signed_contract_file', 'supporting_documents',
                'farm_name', 'farm_registration_number', 'supplier_contact_person', 'supplier_phone', 'supplier_email',
                'location_district', 'location_sector', 'species_covered', 'estimated_quantity', 'delivery_frequency',
                'animal_health_cert_requirement', 'veterinary_inspection_requirement', 'animal_welfare_compliance',
                'transport_responsibility', 'vehicle_plate', 'driver_name',
            ]);
        });
    }
};
