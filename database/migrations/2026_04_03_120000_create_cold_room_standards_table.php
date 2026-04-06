<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cold_room_standards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 32); // chiller | freezer
            $table->decimal('min_temperature', 6, 2);
            $table->decimal('max_temperature', 6, 2);
            $table->unsignedInteger('tolerance_minutes')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cold_room_standards');
    }
};
