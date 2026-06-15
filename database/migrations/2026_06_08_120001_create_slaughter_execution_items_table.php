<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slaughter_execution_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slaughter_execution_id')
                ->constrained('slaughter_executions')
                ->cascadeOnDelete();
            $table->foreignId('animal_intake_item_id')
                ->constrained('animal_intake_items')
                ->cascadeOnDelete();
            $table->decimal('meat_quantity_kg', 8, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(
                ['slaughter_execution_id', 'animal_intake_item_id'],
                'se_items_execution_item_unique',
            );
            $table->index('animal_intake_item_id', 'se_items_intake_item_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slaughter_execution_items');
    }
};
