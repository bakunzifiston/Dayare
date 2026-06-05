<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_confirmations', function (Blueprint $table) {
            if (! Schema::hasColumn('delivery_confirmations', 'received_unit')) {
                $table->string('received_unit', 30)->default('units')->after('received_quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('delivery_confirmations', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_confirmations', 'received_unit')) {
                $table->dropColumn('received_unit');
            }
        });
    }
};
