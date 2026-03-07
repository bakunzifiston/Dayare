<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('demand_number', 100)->nullable()->unique();
            $table->string('title');
            $table->foreignId('destination_facility_id')->nullable()->constrained('facilities')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();

            $table->string('client_name')->nullable();
            $table->string('client_company')->nullable();
            $table->string('client_country', 100)->nullable();
            $table->string('client_contact')->nullable();
            $table->text('client_address')->nullable();

            $table->string('species', 50);
            $table->string('product_description')->nullable();
            $table->decimal('quantity', 14, 2);
            $table->string('quantity_unit', 20)->default('kg');
            $table->date('requested_delivery_date');
            $table->string('status', 30)->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demands');
    }
};
