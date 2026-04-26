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
        Schema::table('animal_intakes', function (Blueprint $table) {
            $table->string('country')->nullable()->after('farm_registration_number');
            $table->string('province')->nullable()->after('country');
            $table->string('district')->nullable()->after('province');
            $table->string('sector')->nullable()->after('district');
            $table->string('cell')->nullable()->after('sector');
            $table->string('village')->nullable()->after('cell');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('country')->nullable()->after('address_line_2');
            $table->string('province')->nullable()->after('country');
            $table->string('district')->nullable()->after('province');
            $table->string('sector')->nullable()->after('district');
            $table->string('cell')->nullable()->after('sector');
            $table->string('village')->nullable()->after('cell');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('animal_intakes', function (Blueprint $table) {
            $table->dropColumn(['country', 'province', 'district', 'sector', 'cell', 'village']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['country', 'province', 'district', 'sector', 'cell', 'village']);
        });
    }
};
