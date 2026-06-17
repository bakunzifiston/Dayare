<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('butcher_disposal_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('butcher_inventory_batches')->cascadeOnDelete();
            $table->decimal('weight_disposed_kg', 12, 3);
            $table->string('reason', 32);
            $table->timestamp('disposed_at');
            $table->foreignId('disposed_by')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'disposed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('butcher_disposal_logs');
    }
};
