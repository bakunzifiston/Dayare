<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_mortem_inspection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_mortem_inspection_id')
                ->constrained('post_mortem_inspections')
                ->cascadeOnDelete();
            $table->foreignId('batch_item_id')
                ->constrained('batch_items')
                ->cascadeOnDelete();
            $table->foreignId('animal_intake_item_id')
                ->constrained('animal_intake_items')
                ->cascadeOnDelete();
            $table->string('outcome', 30);
            $table->text('outcome_notes')->nullable();
            $table->decimal('carcass_weight_kg', 8, 2)->nullable();
            $table->timestamps();

            $table->unique(
                ['post_mortem_inspection_id', 'batch_item_id'],
                'pm_items_inspection_batch_unique',
            );
            $table->index('batch_item_id', 'pm_items_batch_item_index');
            $table->index('animal_intake_item_id', 'pm_items_animal_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_mortem_inspection_items');
    }
};
