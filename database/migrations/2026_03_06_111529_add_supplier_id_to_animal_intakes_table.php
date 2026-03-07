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
            $table->foreignId('supplier_id')->nullable()->after('facility_id')->constrained('suppliers')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('animal_intakes', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
        });
    }
};
