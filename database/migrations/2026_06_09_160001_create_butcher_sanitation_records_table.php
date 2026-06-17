<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('butcher_sanitation_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outlet_id')->constrained('butcher_outlets')->cascadeOnDelete();
            $table->string('equipment_name');
            $table->string('cleaning_type', 32);
            $table->string('chemical_used')->nullable();
            $table->timestamp('performed_at');
            $table->foreignId('performed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('next_due_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'next_due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('butcher_sanitation_records');
    }
};
