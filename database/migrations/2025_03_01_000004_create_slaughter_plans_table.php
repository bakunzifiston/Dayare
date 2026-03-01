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
        Schema::create('slaughter_plans', function (Blueprint $table) {
            $table->id();
            $table->date('slaughter_date');
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspector_id')->constrained()->cascadeOnDelete();
            $table->string('species');
            $table->unsignedInteger('number_of_animals_scheduled');
            $table->string('status')->default('planned'); // planned, approved
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slaughter_plans');
    }
};
