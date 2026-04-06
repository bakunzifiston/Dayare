<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cold_room_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cold_room_id')->constrained('cold_rooms')->cascadeOnDelete();
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->string('status', 32)->default('open'); // open | closed
            $table->timestamps();

            $table->index(['cold_room_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cold_room_violations');
    }
};
