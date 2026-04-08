<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_mortem_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_mortem_inspection_id')->constrained('post_mortem_inspections')->cascadeOnDelete();
            $table->string('category');
            $table->string('item');
            $table->string('value');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['post_mortem_inspection_id', 'category']);
            $table->index(['post_mortem_inspection_id', 'item']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_mortem_observations');
    }
};
