<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('butcher_inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_id')->unique()->constrained('butcher_deliveries')->cascadeOnDelete();
            $table->foreignId('outlet_id')->constrained('butcher_outlets')->cascadeOnDelete();
            $table->string('batch_number')->unique();
            $table->string('meat_type', 32);
            $table->decimal('quantity_kg', 12, 3);
            $table->decimal('remaining_quantity_kg', 12, 3);
            $table->decimal('unit_cost_per_kg', 12, 2);
            $table->decimal('total_cost', 14, 2);
            $table->string('certificate_ref', 100)->nullable();
            $table->string('certificate_issuer')->nullable();
            $table->string('condition', 32);
            $table->string('status', 32)->default('available');
            $table->timestamp('received_at');
            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index(['outlet_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('butcher_inventory_batches');
    }
};
