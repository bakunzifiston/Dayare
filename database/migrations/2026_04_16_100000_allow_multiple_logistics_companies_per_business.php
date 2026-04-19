<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_companies', function (Blueprint $table) {
            $table->dropUnique('logistics_companies_business_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('logistics_companies', function (Blueprint $table) {
            $table->unique('business_id');
        });
    }
};
