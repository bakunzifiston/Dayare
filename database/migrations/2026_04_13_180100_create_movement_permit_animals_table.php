<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movement_permit_animals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movement_permit_id')->constrained('movement_permits')->cascadeOnDelete();
            $table->foreignId('livestock_id')->nullable()->constrained('livestock')->nullOnDelete();
            $table->string('animal_identifier', 120)->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->timestamps();

            $table->index(['movement_permit_id', 'livestock_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movement_permit_animals');
    }
};

