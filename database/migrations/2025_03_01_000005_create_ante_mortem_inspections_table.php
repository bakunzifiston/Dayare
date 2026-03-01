<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ante_mortem_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slaughter_plan_id')->constrained('slaughter_plans')->cascadeOnDelete();
            $table->foreignId('inspector_id')->constrained()->cascadeOnDelete();
            $table->string('species');
            $table->unsignedInteger('number_examined');
            $table->unsignedInteger('number_approved');
            $table->unsignedInteger('number_rejected');
            $table->text('notes')->nullable();
            $table->date('inspection_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ante_mortem_inspections');
    }
};
