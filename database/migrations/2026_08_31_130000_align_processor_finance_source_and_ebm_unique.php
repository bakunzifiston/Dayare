<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('finance_invoices', 'invoice_type') && Schema::hasColumn('finance_invoices', 'source_type')) {
            DB::table('finance_invoices')
                ->whereIn('invoice_type', ['intake', 'delivery', 'manual'])
                ->update(['source_type' => DB::raw('invoice_type')]);
        }

        if (Schema::hasTable('finance_ebm_records') && Schema::hasColumn('finance_ebm_records', 'business_id')) {
            $indexName = 'finance_ebm_records_business_id_ebm_invoice_number_unique';
            $hasIndex = collect(Schema::getIndexes('finance_ebm_records'))
                ->contains(fn (array $index) => ($index['name'] ?? '') === $indexName);

            if (! $hasIndex) {
                Schema::table('finance_ebm_records', function (Blueprint $table) use ($indexName) {
                    $table->unique(['business_id', 'ebm_invoice_number'], $indexName);
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('finance_ebm_records')) {
            Schema::table('finance_ebm_records', function (Blueprint $table) {
                $table->dropUnique('finance_ebm_records_business_id_ebm_invoice_number_unique');
            });
        }
    }
};
