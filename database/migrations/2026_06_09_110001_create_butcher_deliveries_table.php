<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('butcher_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('butcher_purchase_orders')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('butcher_suppliers')->cascadeOnDelete();
            $table->string('delivery_number')->unique();
            $table->string('meat_type', 32);
            $table->decimal('received_weight_kg', 12, 3);
            $table->decimal('unit_cost_per_kg', 12, 2);
            $table->decimal('total_cost', 14, 2);
            $table->string('condition', 32);
            $table->timestamp('received_at');
            $table->foreignId('received_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('outlet_id')->constrained('butcher_outlets')->cascadeOnDelete();
            $table->string('certificate_ref', 100)->nullable();
            $table->string('certificate_issuer')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'received_at']);
            $table->index(['business_id', 'condition']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('butcher_deliveries');
    }
};
