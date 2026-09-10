<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('butcher_orders', function (Blueprint $table) {
            $table->foreignId('sale_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('butcher_sales')
                ->nullOnDelete();
            $table->foreignId('outlet_id')
                ->nullable()
                ->after('sale_id')
                ->constrained('butcher_outlets')
                ->nullOnDelete();

            $table->unique('sale_id');
        });

        Schema::create('butcher_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_item_id')->constrained('butcher_sale_items')->cascadeOnDelete();
            $table->foreignId('cut_output_id')->nullable()->constrained('butcher_cut_outputs')->nullOnDelete();
            $table->decimal('quantity_kg', 12, 3);
            $table->string('reason', 255)->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at');
            $table->decimal('credit_reversed', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['business_id', 'processed_at']);
            $table->index('sale_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('butcher_returns');

        Schema::table('butcher_orders', function (Blueprint $table) {
            $table->dropUnique(['sale_id']);
            $table->dropConstrainedForeignId('outlet_id');
            $table->dropConstrainedForeignId('sale_id');
        });
    }
};
