<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cold_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 32); // chiller | freezer
            $table->decimal('capacity', 12, 2)->nullable();
            $table->foreignId('standard_id')->nullable()->constrained('cold_room_standards')->nullOnDelete();
            $table->timestamps();

            $table->index(['facility_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cold_rooms');
    }
};
