<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('butcher_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('butcher_customers')->cascadeOnDelete();
            $table->string('order_number')->unique();
            $table->date('order_date');
            $table->date('delivery_date')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('deposit_paid', 12, 2)->default(0);
            $table->string('status', 32)->default('pending');
            $table->timestamps();

            $table->index(['business_id', 'status']);
        });

        Schema::create('butcher_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('butcher_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('butcher_products')->cascadeOnDelete();
            $table->decimal('quantity_kg', 12, 3)->default(0);
            $table->unsignedInteger('quantity_units')->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('butcher_order_items');
        Schema::dropIfExists('butcher_orders');
    }
};
