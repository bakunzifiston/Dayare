<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('province_id')->nullable()->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('sector_id')->nullable()->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('cell_id')->nullable()->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('village_id')->nullable()->constrained('administrative_divisions')->nullOnDelete();
            $table->json('animal_types')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farms');
    }
};
