<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ante_mortem_inspection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ante_mortem_inspection_id')
                ->constrained('ante_mortem_inspections')
                ->cascadeOnDelete();
            $table->foreignId('animal_intake_item_id')
                ->constrained('animal_intake_items')
                ->cascadeOnDelete();
            $table->string('outcome', 30);
            $table->text('outcome_notes')->nullable();
            $table->timestamps();

            $table->unique(
                ['ante_mortem_inspection_id', 'animal_intake_item_id'],
                'am_inspection_items_inspection_item_unique',
            );
            $table->index('animal_intake_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ante_mortem_inspection_items');
    }
};
