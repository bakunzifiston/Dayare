<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('name');
            $table->string('registration_number')->unique();
            $table->string('tax_id')->nullable();
            $table->string('license_type', 120);
            $table->date('license_expiry_date');
            $table->json('operating_regions')->nullable();
            $table->string('contact_person', 150)->nullable();
            $table->timestamps();

            $table->unique('business_id');
            $table->index(['business_id', 'license_expiry_date']);
        });

        Schema::create('logistics_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('logistics_companies')->cascadeOnDelete();
            $table->string('plate_number', 60)->unique();
            $table->string('type', 80);
            $table->decimal('max_weight', 12, 2)->nullable();
            $table->unsignedInteger('max_units');
            $table->string('status', 30)->default('available');
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        Schema::create('logistics_drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('logistics_companies')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('license_number', 120)->unique();
            $table->date('license_expiry');
            $table->string('status', 30)->default('available');
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index('license_expiry');
        });

        Schema::create('logistics_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('logistics_companies')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('businesses')->restrictOnDelete();
            $table->string('pickup_location');
            $table->string('delivery_location');
            $table->string('species', 120)->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('weight', 12, 2)->nullable();
            $table->date('requested_date');
            $table->string('priority', 20)->default('normal');
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['client_id', 'requested_date']);
        });

        Schema::create('logistics_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('logistics_companies')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('logistics_vehicles')->restrictOnDelete();
            $table->foreignId('driver_id')->constrained('logistics_drivers')->restrictOnDelete();
            $table->dateTime('planned_departure');
            $table->dateTime('planned_arrival');
            $table->dateTime('actual_departure')->nullable();
            $table->dateTime('actual_arrival')->nullable();
            $table->string('status', 30)->default('scheduled');
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['vehicle_id', 'status']);
            $table->index(['driver_id', 'status']);
        });

        Schema::create('logistics_trip_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('logistics_trips')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('logistics_orders')->restrictOnDelete();
            $table->unsignedInteger('allocated_quantity');
            $table->unsignedInteger('delivered_quantity')->default(0);
            $table->unsignedInteger('loss_quantity')->default(0);
            $table->timestamps();

            $table->unique(['trip_id', 'order_id']);
        });

        Schema::create('logistics_tracking_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('logistics_trips')->cascadeOnDelete();
            $table->dateTime('timestamp');
            $table->string('location', 255);
            $table->string('status', 30);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['trip_id', 'timestamp']);
        });

        Schema::create('logistics_compliance_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('logistics_trips')->cascadeOnDelete();
            $table->string('type', 50);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->index(['trip_id', 'type', 'status']);
        });

        Schema::create('logistics_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('logistics_trips')->cascadeOnDelete();
            $table->decimal('base_cost', 12, 2)->default(0);
            $table->decimal('cost_per_km', 12, 2)->default(0);
            $table->decimal('distance_km', 12, 2)->default(0);
            $table->decimal('cost_per_unit', 12, 2)->default(0);
            $table->decimal('extra_charges', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('payment_status', 20)->default('pending');
            $table->timestamps();

            $table->unique('trip_id');
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_invoices');
        Schema::dropIfExists('logistics_compliance_documents');
        Schema::dropIfExists('logistics_tracking_logs');
        Schema::dropIfExists('logistics_trip_orders');
        Schema::dropIfExists('logistics_trips');
        Schema::dropIfExists('logistics_orders');
        Schema::dropIfExists('logistics_drivers');
        Schema::dropIfExists('logistics_vehicles');
        Schema::dropIfExists('logistics_companies');
    }
};

