<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('feed_name');
            $table->string('feed_code', 64);
            $table->string('feed_category', 64);
            $table->string('feed_form', 32);
            $table->string('unit', 32)->default('kg');
            $table->decimal('protein_percentage', 8, 2)->nullable();
            $table->decimal('energy_value', 12, 2)->nullable();
            $table->text('nutritional_value')->nullable();
            $table->string('manufacturer')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 32)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_id', 'feed_code']);
            $table->index(['business_id', 'status']);
        });

        Schema::create('feed_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('supplier_name');
            $table->string('supplier_code', 64);
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->json('supplied_feed_types')->nullable();
            $table->string('status', 32)->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_id', 'supplier_code']);
        });

        Schema::create('feed_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feed_type_id')->constrained()->cascadeOnDelete();
            $table->string('inventory_code', 64)->unique();
            $table->foreignId('supplier_id')->nullable()->constrained('feed_suppliers')->nullOnDelete();
            $table->decimal('quantity_received', 14, 3);
            $table->decimal('quantity_remaining', 14, 3);
            $table->decimal('unit_cost', 14, 2)->nullable();
            $table->decimal('total_cost', 14, 2)->nullable();
            $table->date('purchase_date');
            $table->date('expiry_date')->nullable();
            $table->string('storage_location')->nullable();
            $table->decimal('reorder_level', 14, 3)->nullable();
            $table->string('batch_number')->nullable();
            $table->string('status', 32)->default('available');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['feed_type_id', 'status']);
            $table->index('expiry_date');
        });

        Schema::create('feeding_records', function (Blueprint $table) {
            $table->id();
            $table->string('feeding_code', 64)->unique();
            $table->foreignId('animal_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('livestock_id')->nullable()->constrained('livestock')->nullOnDelete();
            $table->foreignId('feed_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('feed_inventory_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->string('feeding_method', 32)->nullable();
            $table->time('feeding_time')->nullable();
            $table->date('feeding_date');
            $table->string('fed_by')->nullable();
            $table->string('appetite_status', 32)->nullable();
            $table->boolean('water_provided')->default(false);
            $table->string('feeding_response')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['feeding_date', 'feed_type_id']);
            $table->index(['animal_id', 'feeding_date']);
            $table->index(['livestock_id', 'feeding_date']);
        });

        Schema::create('feed_inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feed_inventory_id')->constrained()->cascadeOnDelete();
            $table->string('movement_type', 32);
            $table->decimal('quantity_change', 14, 3);
            $table->decimal('balance_after', 14, 3);
            $table->foreignId('feeding_record_id')->nullable()->constrained('feeding_records')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['feed_inventory_id', 'created_at']);
        });

        Schema::create('feeding_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('schedule_name');
            $table->foreignId('animal_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('livestock_id')->nullable()->constrained('livestock')->nullOnDelete();
            $table->foreignId('feed_type_id')->constrained()->restrictOnDelete();
            $table->time('feeding_time');
            $table->string('feeding_frequency', 32);
            $table->decimal('quantity', 14, 3);
            $table->text('instructions')->nullable();
            $table->string('status', 32)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feeding_schedules');
        Schema::dropIfExists('feeding_records');
        Schema::dropIfExists('feed_inventory_movements');
        Schema::dropIfExists('feed_inventories');
        Schema::dropIfExists('feed_suppliers');
        Schema::dropIfExists('feed_types');
    }
};
