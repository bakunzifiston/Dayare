<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ante_mortem_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ante_mortem_inspection_id')->constrained('ante_mortem_inspections')->cascadeOnDelete();
            $table->string('item');
            $table->string('value');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['ante_mortem_inspection_id', 'item']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ante_mortem_observations');
    }
};
