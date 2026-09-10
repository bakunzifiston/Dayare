<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('butcher_inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outlet_id')->nullable()->constrained('butcher_outlets')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('butcher_inventory_batches')->nullOnDelete();
            $table->foreignId('cut_output_id')->nullable()->constrained('butcher_cut_outputs')->nullOnDelete();
            $table->string('type', 40);
            $table->decimal('quantity_kg', 12, 3);
            $table->decimal('before_qty', 12, 3)->nullable();
            $table->decimal('after_qty', 12, 3)->nullable();
            $table->nullableMorphs('reference');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['batch_id', 'occurred_at']);
            $table->index(['business_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('butcher_inventory_movements');
    }
};
