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
        Schema::create('slaughter_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slaughter_plan_id')->constrained('slaughter_plans')->cascadeOnDelete();
            $table->unsignedInteger('actual_animals_slaughtered');
            $table->dateTime('slaughter_time');
            $table->string('status')->default('completed'); // scheduled, in_progress, completed, cancelled
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slaughter_executions');
    }
};
