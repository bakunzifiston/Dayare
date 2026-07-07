<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animal_intake_items', function (Blueprint $table) {
            $table->decimal('service_fee', 12, 2)->default(0)->after('unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('animal_intake_items', function (Blueprint $table) {
            $table->dropColumn('service_fee');
        });
    }
};
