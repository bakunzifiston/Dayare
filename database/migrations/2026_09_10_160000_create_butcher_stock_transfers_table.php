<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('butcher_stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('from_outlet_id')->constrained('butcher_outlets')->cascadeOnDelete();
            $table->foreignId('to_outlet_id')->constrained('butcher_outlets')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('butcher_inventory_batches')->cascadeOnDelete();
            $table->foreignId('destination_batch_id')->nullable()->constrained('butcher_inventory_batches')->nullOnDelete();
            $table->decimal('quantity_kg', 12, 3);
            $table->foreignId('transferred_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('transferred_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'transferred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('butcher_stock_transfers');
    }
};
