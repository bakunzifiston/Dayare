<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow delivery confirmation when recipient is outside / not a registered facility.
     */
    public function up(): void
    {
        Schema::table('delivery_confirmations', function (Blueprint $table) {
            $table->dropForeign(['receiving_facility_id']);
            $table->unsignedBigInteger('receiving_facility_id')->nullable()->change();
            $table->foreign('receiving_facility_id')->references('id')->on('facilities')->nullOnDelete();
            $table->string('receiver_country', 100)->nullable()->after('receiver_name');
            $table->text('receiver_address')->nullable()->after('receiver_country');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_confirmations', function (Blueprint $table) {
            $table->dropForeign(['receiving_facility_id']);
            $table->dropColumn(['receiver_country', 'receiver_address']);
            $table->unsignedBigInteger('receiving_facility_id')->nullable(false)->change();
            $table->foreign('receiving_facility_id')->references('id')->on('facilities')->cascadeOnDelete();
        });
    }
};
