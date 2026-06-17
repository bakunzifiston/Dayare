<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('butcher_cut_outputs', function (Blueprint $table) {
            $table->decimal('remaining_weight_kg', 12, 3)->default(0)->after('weight_kg');
        });

        DB::table('butcher_cut_outputs')->update([
            'remaining_weight_kg' => DB::raw('weight_kg'),
        ]);
    }

    public function down(): void
    {
        Schema::table('butcher_cut_outputs', function (Blueprint $table) {
            $table->dropColumn('remaining_weight_kg');
        });
    }
};
