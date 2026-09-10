<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('butcher_inventory_batches', function (Blueprint $table) {
            $table->boolean('temperature_breach')->default(false)->after('storage_location');
            $table->timestamp('temperature_breach_at')->nullable()->after('temperature_breach');
        });

        Schema::create('butcher_compliance_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('context_type', 64);
            $table->unsignedBigInteger('context_id')->nullable();
            $table->foreignId('batch_id')->nullable()->constrained('butcher_inventory_batches')->nullOnDelete();
            $table->foreignId('cut_output_id')->nullable()->constrained('butcher_cut_outputs')->nullOnDelete();
            $table->text('reason');
            $table->json('issues')->nullable();
            $table->foreignId('overridden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('overridden_at');
            $table->timestamps();

            $table->index(['business_id', 'context_type', 'context_id'], 'butcher_compliance_overrides_ctx_idx');
            $table->index(['batch_id', 'overridden_at'], 'butcher_compliance_overrides_batch_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('butcher_compliance_overrides');

        Schema::table('butcher_inventory_batches', function (Blueprint $table) {
            $table->dropColumn(['temperature_breach', 'temperature_breach_at']);
        });
    }
};
