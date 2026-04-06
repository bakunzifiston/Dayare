<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extends warehouse_storages (cold room storage records) with optional physical cold room link.
     */
    public function up(): void
    {
        Schema::table('warehouse_storages', function (Blueprint $table) {
            $table->foreignId('cold_room_id')
                ->nullable()
                ->after('warehouse_facility_id')
                ->constrained('cold_rooms')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_storages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cold_room_id');
        });
    }
};
