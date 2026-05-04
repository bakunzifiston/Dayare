<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_invoices', function (Blueprint $table) {
            $table->foreignId('animal_intake_id')
                ->nullable()
                ->after('client_id')
                ->constrained('animal_intakes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('finance_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('animal_intake_id');
        });
    }
};
