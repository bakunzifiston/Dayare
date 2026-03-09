<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_storages', function (Blueprint $table) {
            $table->string('quantity_unit', 20)->default('kg')->after('quantity_stored');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_storages', function (Blueprint $table) {
            $table->dropColumn('quantity_unit');
        });
    }
};
