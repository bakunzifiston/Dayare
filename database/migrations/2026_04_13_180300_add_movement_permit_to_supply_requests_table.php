<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supply_requests', function (Blueprint $table) {
            $table->foreignId('movement_permit_id')
                ->nullable()
                ->after('source_farm_id')
                ->constrained('movement_permits')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('supply_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('movement_permit_id');
        });
    }
};

