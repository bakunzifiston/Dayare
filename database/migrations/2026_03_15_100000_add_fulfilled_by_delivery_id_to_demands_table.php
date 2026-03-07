<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demands', function (Blueprint $table) {
            $table->foreignId('fulfilled_by_delivery_id')
                ->nullable()
                ->after('notes')
                ->constrained('delivery_confirmations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('demands', function (Blueprint $table) {
            $table->dropForeign(['fulfilled_by_delivery_id']);
        });
    }
};
