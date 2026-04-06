<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cold_room_temperature_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cold_room_id')->constrained('cold_rooms')->cascadeOnDelete();
            $table->decimal('temperature', 6, 2);
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['cold_room_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cold_room_temperature_logs');
    }
};
