<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('facility_id')->constrained('clients')->nullOnDelete();
        });
        Schema::table('delivery_confirmations', function (Blueprint $table) {
            $table->foreignId('contract_id')->nullable()->after('client_id')->constrained('contracts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('delivery_confirmations', function (Blueprint $table) {
            $table->dropForeign(['contract_id']);
        });
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
        });
    }
};
