<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('butcher_inventory_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('butcher_inventory_batches')->cascadeOnDelete();
            $table->decimal('weight_change_kg', 12, 3);
            $table->decimal('previous_weight_kg', 12, 3);
            $table->decimal('new_weight_kg', 12, 3);
            $table->string('reason', 40);
            $table->timestamp('adjusted_at');
            $table->foreignId('adjusted_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('stock_count_line_id')->nullable()->constrained('butcher_stock_count_lines')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'adjusted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('butcher_inventory_adjustments');
    }
};
