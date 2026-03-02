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
        Schema::create('temperature_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_storage_id')->constrained()->cascadeOnDelete();
            $table->decimal('recorded_temperature', 5, 2); // °C
            $table->dateTime('recorded_at');
            $table->string('recorded_by', 255)->nullable();
            $table->string('status', 50)->default('normal'); // normal / warning / critical
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temperature_logs');
    }
};
