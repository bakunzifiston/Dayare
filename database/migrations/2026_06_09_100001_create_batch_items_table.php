<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')
                ->constrained('batches')
                ->cascadeOnDelete();
            $table->foreignId('slaughter_execution_item_id')
                ->constrained('slaughter_execution_items')
                ->cascadeOnDelete();
            $table->foreignId('animal_intake_item_id')
                ->constrained('animal_intake_items')
                ->cascadeOnDelete();
            $table->decimal('meat_quantity_kg', 8, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(
                ['batch_id', 'animal_intake_item_id'],
                'batch_items_batch_animal_unique',
            );
            $table->index('slaughter_execution_item_id', 'batch_items_exec_item_index');
            $table->index('animal_intake_item_id', 'batch_items_animal_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_items');
    }
};
