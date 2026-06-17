<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('butcher_delivery_rejections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_id')->unique()->constrained('butcher_deliveries')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('butcher_suppliers')->cascadeOnDelete();
            $table->string('meat_type', 32);
            $table->decimal('rejected_weight_kg', 12, 3);
            $table->string('certificate_ref', 100)->nullable();
            $table->string('certificate_issuer')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('rejected_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('rejected_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('butcher_delivery_rejections');
    }
};
