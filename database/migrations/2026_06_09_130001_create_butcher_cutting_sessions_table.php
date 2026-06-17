<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('butcher_cutting_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outlet_id')->constrained('butcher_outlets')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('butcher_inventory_batches')->cascadeOnDelete();
            $table->string('session_number')->unique();
            $table->decimal('source_weight_kg', 12, 3);
            $table->decimal('total_cuts_weight_kg', 12, 3)->default(0);
            $table->decimal('wastage_kg', 12, 3)->nullable();
            $table->decimal('wastage_pct', 5, 2)->nullable();
            $table->date('session_date');
            $table->string('status', 32)->default('open');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('butcher_cutting_sessions');
    }
};
