<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_cost_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('source');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 50)->default('other');
            $table->decimal('amount', 14, 2)->default(0);
            $table->date('allocation_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'allocation_date']);
            $table->index(['batch_id', 'category']);
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_cost_allocations');
    }
};
