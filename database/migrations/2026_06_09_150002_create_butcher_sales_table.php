<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('butcher_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outlet_id')->constrained('butcher_outlets')->cascadeOnDelete();
            $table->string('sale_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained('butcher_customers')->nullOnDelete();
            $table->date('sale_date');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('payment_method', 32);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('change_given', 12, 2)->default(0);
            $table->string('status', 32)->default('completed');
            $table->foreignId('sold_by')->constrained('users')->cascadeOnDelete();
            $table->string('receipt_path')->nullable();
            $table->string('invoice_path')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'sale_date']);
            $table->index(['business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('butcher_sales');
    }
};
