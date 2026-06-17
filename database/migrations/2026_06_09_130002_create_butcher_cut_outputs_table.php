<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('butcher_cut_outputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('butcher_cutting_sessions')->cascadeOnDelete();
            $table->foreignId('cut_type_id')->constrained('butcher_cut_types')->cascadeOnDelete();
            $table->decimal('weight_kg', 12, 3);
            $table->decimal('unit_cost_per_kg', 12, 2);
            $table->boolean('label_printed')->default(false);
            $table->string('label_path')->nullable();
            $table->timestamps();

            $table->index(['session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('butcher_cut_outputs');
    }
};
