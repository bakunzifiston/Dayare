<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('butcher_temperature_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outlet_id')->constrained('butcher_outlets')->cascadeOnDelete();
            $table->string('storage_location');
            $table->string('storage_type', 16)->default('fresh');
            $table->decimal('temperature_celsius', 5, 2);
            $table->timestamp('logged_at');
            $table->foreignId('logged_by')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_breach')->default(false);
            $table->text('breach_note')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'logged_at']);
            $table->index(['business_id', 'is_breach']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('butcher_temperature_logs');
    }
};
