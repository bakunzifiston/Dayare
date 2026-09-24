<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('post_mortem_condemned_organs')) {
            Schema::create('post_mortem_condemned_organs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('post_mortem_inspection_item_id');
                $table->string('organ_name', 255);
                $table->decimal('weight_kg', 8, 2);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->foreign('post_mortem_inspection_item_id', 'pm_condemned_organs_item_fk')
                    ->references('id')
                    ->on('post_mortem_inspection_items')
                    ->cascadeOnDelete();

                $table->index(
                    ['post_mortem_inspection_item_id', 'sort_order'],
                    'pm_condemned_organs_item_sort_idx',
                );
            });

            return;
        }

        // Recover from a previous attempt that created the table but failed on a too-long FK name.
        if (! $this->hasForeignKey('post_mortem_condemned_organs', 'pm_condemned_organs_item_fk')) {
            Schema::table('post_mortem_condemned_organs', function (Blueprint $table) {
                $table->foreign('post_mortem_inspection_item_id', 'pm_condemned_organs_item_fk')
                    ->references('id')
                    ->on('post_mortem_inspection_items')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('post_mortem_condemned_organs');
    }

    private function hasForeignKey(string $table, string $name): bool
    {
        $database = Schema::getConnection()->getDatabaseName();

        $count = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $name)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->count();

        return $count > 0;
    }
};
