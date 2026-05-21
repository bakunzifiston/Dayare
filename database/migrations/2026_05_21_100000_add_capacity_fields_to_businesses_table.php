<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->json('cooling_tanks')->nullable()->after('baseline_revenue');
            $table->unsignedInteger('daily_milk_volume_avg')->nullable()->after('cooling_tanks');
            $table->unsignedInteger('daily_milk_volume_max')->nullable()->after('daily_milk_volume_avg');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'cooling_tanks',
                'daily_milk_volume_avg',
                'daily_milk_volume_max',
            ]);
        });
    }
};
