<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_mortem_inspection_items', function (Blueprint $table) {
            $table->decimal('condemned_weight_kg', 8, 2)->nullable()->after('carcass_weight_kg');
        });
    }

    public function down(): void
    {
        Schema::table('post_mortem_inspection_items', function (Blueprint $table) {
            $table->dropColumn('condemned_weight_kg');
        });
    }
};
