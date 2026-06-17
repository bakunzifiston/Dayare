<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('butcher_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('butcher_sales')->cascadeOnDelete();
            $table->foreignId('cut_output_id')->nullable()->constrained('butcher_cut_outputs')->nullOnDelete();
            $table->foreignId('product_id')->constrained('butcher_products')->cascadeOnDelete();
            $table->decimal('quantity_kg', 12, 3)->default(0);
            $table->unsignedInteger('quantity_units')->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();

            $table->index(['sale_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('butcher_sale_items');
    }
};
