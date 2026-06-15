<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_mortem_observations', function (Blueprint $table) {
            $table->foreignId('animal_intake_item_id')
                ->nullable()
                ->after('post_mortem_inspection_id')
                ->constrained('animal_intake_items')
                ->nullOnDelete();

            $table->index(
                ['post_mortem_inspection_id', 'animal_intake_item_id', 'item'],
                'pm_observations_inspection_animal_item_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::table('post_mortem_observations', function (Blueprint $table) {
            $table->dropIndex('pm_observations_inspection_animal_item_idx');
            $table->dropConstrainedForeignId('animal_intake_item_id');
        });
    }
};
