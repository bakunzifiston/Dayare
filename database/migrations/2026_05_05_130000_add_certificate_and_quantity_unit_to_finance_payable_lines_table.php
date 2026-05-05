<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_payable_lines', function (Blueprint $table) {
            $table->foreignId('certificate_id')
                ->nullable()
                ->after('batch_id')
                ->constrained()
                ->nullOnDelete();
            $table->string('quantity_unit', 50)->nullable()->after('quantity');
            $table->index('certificate_id');
        });
    }

    public function down(): void
    {
        Schema::table('finance_payable_lines', function (Blueprint $table) {
            $table->dropForeign(['certificate_id']);
            $table->dropIndex(['certificate_id']);
            $table->dropColumn(['certificate_id', 'quantity_unit']);
        });
    }
};
