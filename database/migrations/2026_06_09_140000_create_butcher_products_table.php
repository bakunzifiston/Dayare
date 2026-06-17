<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('butcher_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cut_type_id')->nullable()->constrained('butcher_cut_types')->nullOnDelete();
            $table->string('name');
            $table->string('meat_type', 32);
            $table->string('unit', 32)->default('per_kg');
            $table->decimal('default_price', 12, 2);
            $table->decimal('avg_cost_per_kg', 12, 2)->default(0);
            $table->decimal('margin_pct', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['business_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('butcher_products');
    }
};
