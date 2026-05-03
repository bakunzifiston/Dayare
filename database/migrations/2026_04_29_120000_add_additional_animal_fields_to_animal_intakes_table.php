<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animal_intakes', function (Blueprint $table) {
            $table->string('species_ear_tag', 100)->nullable()->after('species');
            $table->string('sex', 20)->nullable()->after('species_ear_tag');
            $table->unsignedInteger('age')->nullable()->after('sex');
            $table->string('movement_permit_no', 100)->nullable()->after('farm_registration_number');
            $table->text('observation')->nullable()->after('animal_identification_numbers');
        });
    }

    public function down(): void
    {
        Schema::table('animal_intakes', function (Blueprint $table) {
            $table->dropColumn([
                'species_ear_tag',
                'sex',
                'age',
                'movement_permit_no',
                'observation',
            ]);
        });
    }
};
