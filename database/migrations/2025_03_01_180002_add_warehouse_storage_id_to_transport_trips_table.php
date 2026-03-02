<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * TransportTrip references warehouse_storage_id (released storage) for compliance.
     */
    public function up(): void
    {
        Schema::table('transport_trips', function (Blueprint $table) {
            $table->foreignId('warehouse_storage_id')->nullable()->after('certificate_id')->constrained('warehouse_storages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transport_trips', function (Blueprint $table) {
            $table->dropForeign(['warehouse_storage_id']);
        });
    }
};
