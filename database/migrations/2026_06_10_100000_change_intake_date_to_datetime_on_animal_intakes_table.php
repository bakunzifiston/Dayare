<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store intake date together with time of receipt (was date-only).
     */
    public function up(): void
    {
        Schema::table('animal_intakes', function (Blueprint $table) {
            $table->dateTime('intake_date')->change();
        });
    }

    public function down(): void
    {
        Schema::table('animal_intakes', function (Blueprint $table) {
            $table->date('intake_date')->change();
        });
    }
};
