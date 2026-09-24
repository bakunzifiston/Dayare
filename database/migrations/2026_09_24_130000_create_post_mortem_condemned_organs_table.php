<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('post_mortem_condemned_organs')) {
            return;
        }

        Schema::create('post_mortem_condemned_organs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_mortem_inspection_item_id')
                ->constrained('post_mortem_inspection_items')
                ->cascadeOnDelete();
            $table->string('organ_name', 255);
            $table->decimal('weight_kg', 8, 2);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(
                ['post_mortem_inspection_item_id', 'sort_order'],
                'pm_condemned_organs_item_sort_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_mortem_condemned_organs');
    }
};
