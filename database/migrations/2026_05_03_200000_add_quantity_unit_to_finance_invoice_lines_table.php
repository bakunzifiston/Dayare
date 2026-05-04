<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_invoice_lines', function (Blueprint $table) {
            $table->string('quantity_unit', 50)->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('finance_invoice_lines', function (Blueprint $table) {
            $table->dropColumn('quantity_unit');
        });
    }
};
