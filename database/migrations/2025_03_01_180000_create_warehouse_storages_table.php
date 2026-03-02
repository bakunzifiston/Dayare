<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Warehouse (cold storage): track certified meat batches before transport.
     * Facility type = storage.
     */
    public function up(): void
    {
        Schema::create('warehouse_storages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('certificate_id')->constrained()->cascadeOnDelete();
            $table->date('entry_date');
            $table->string('storage_location', 255)->nullable(); // room/freezer name
            $table->decimal('temperature_at_entry', 5, 2)->nullable(); // °C
            $table->unsignedInteger('quantity_stored')->default(0);
            $table->string('status', 50)->default('in_storage'); // in_storage / released / disposed
            $table->date('released_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_storages');
    }
};
