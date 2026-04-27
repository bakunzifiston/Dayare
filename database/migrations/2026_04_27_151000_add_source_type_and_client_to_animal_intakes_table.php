<?php

use App\Models\AnimalIntake;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animal_intakes', function (Blueprint $table) {
            $table->string('source_type', 20)->default(AnimalIntake::SOURCE_TYPE_SUPPLIER)->after('facility_id');
            $table->foreignId('client_id')->nullable()->after('supplier_id')->constrained('clients')->nullOnDelete();
        });

        DB::table('animal_intakes')
            ->whereNull('source_type')
            ->update(['source_type' => AnimalIntake::SOURCE_TYPE_SUPPLIER]);
    }

    public function down(): void
    {
        Schema::table('animal_intakes', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn(['client_id', 'source_type']);
        });
    }
};
