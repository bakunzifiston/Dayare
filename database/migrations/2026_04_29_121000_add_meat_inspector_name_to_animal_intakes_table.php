<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animal_intakes', function (Blueprint $table) {
            $table->string('meat_inspector_name', 255)->nullable()->after('observation');
        });
    }

    public function down(): void
    {
        Schema::table('animal_intakes', function (Blueprint $table) {
            $table->dropColumn('meat_inspector_name');
        });
    }
};
