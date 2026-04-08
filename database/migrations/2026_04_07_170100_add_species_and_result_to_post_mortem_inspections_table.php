<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_mortem_inspections', function (Blueprint $table) {
            $table->string('species')->nullable()->after('inspector_id');
            $table->string('result')->nullable()->after('inspection_date');
        });
    }

    public function down(): void
    {
        Schema::table('post_mortem_inspections', function (Blueprint $table) {
            $table->dropColumn(['species', 'result']);
        });
    }
};
