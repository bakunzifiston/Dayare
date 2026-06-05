<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table): void {
            $table->dropUnique('businesses_business_name_normalized_unique');
            $table->dropUnique('businesses_business_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table): void {
            $table->unique('business_name', 'businesses_business_name_unique');
            $table->unique('business_name_normalized', 'businesses_business_name_normalized_unique');
        });
    }
};
