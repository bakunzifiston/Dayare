<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('butcher_stock_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outlet_id')->nullable()->constrained('butcher_outlets')->nullOnDelete();
            $table->string('count_number', 40);
            $table->string('status', 20)->default('draft');
            $table->date('count_date');
            $table->foreignId('counted_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'count_number']);
            $table->index(['business_id', 'count_date']);
        });

        Schema::create('butcher_stock_count_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_count_id')->constrained('butcher_stock_counts')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('butcher_inventory_batches')->cascadeOnDelete();
            $table->decimal('system_weight_kg', 12, 3);
            $table->decimal('counted_weight_kg', 12, 3)->nullable();
            $table->decimal('variance_kg', 12, 3)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['stock_count_id', 'batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('butcher_stock_count_lines');
        Schema::dropIfExists('butcher_stock_counts');
    }
};
