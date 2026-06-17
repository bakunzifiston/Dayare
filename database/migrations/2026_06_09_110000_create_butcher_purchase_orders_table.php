<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('butcher_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('butcher_suppliers')->cascadeOnDelete();
            $table->string('po_number')->unique();
            $table->string('meat_type', 32);
            $table->decimal('requested_weight_kg', 12, 3);
            $table->date('requested_date');
            $table->string('status', 32)->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('butcher_purchase_orders');
    }
};
