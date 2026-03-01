<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Batch (1) → One Post-Mortem Inspection.
     */
    public function up(): void
    {
        Schema::create('post_mortem_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_mortem_inspections');
    }
};
