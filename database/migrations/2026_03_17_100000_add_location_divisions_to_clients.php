<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('country_id')->nullable()->after('country')->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('province_id')->nullable()->after('country_id')->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->after('province_id')->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('sector_id')->nullable()->after('district_id')->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('cell_id')->nullable()->after('sector_id')->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('village_id')->nullable()->after('cell_id')->constrained('administrative_divisions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropForeign(['province_id']);
            $table->dropForeign(['district_id']);
            $table->dropForeign(['sector_id']);
            $table->dropForeign(['cell_id']);
            $table->dropForeign(['village_id']);
        });
    }
};
