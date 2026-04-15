<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_species', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('species_id')->constrained('species')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['business_id', 'species_id']);
        });

        Schema::create('business_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['business_id', 'unit_id']);
        });

        $now = now();
        $businessIds = DB::table('businesses')->pluck('id')->all();
        $speciesIds = DB::table('species')->where('is_active', true)->pluck('id')->all();
        $unitIds = DB::table('units')->where('is_active', true)->pluck('id')->all();

        $businessSpecies = [];
        foreach ($businessIds as $businessId) {
            foreach ($speciesIds as $speciesId) {
                $businessSpecies[] = [
                    'business_id' => (int) $businessId,
                    'species_id' => (int) $speciesId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($businessSpecies !== []) {
            DB::table('business_species')->insert($businessSpecies);
        }

        $businessUnits = [];
        foreach ($businessIds as $businessId) {
            foreach ($unitIds as $unitId) {
                $businessUnits[] = [
                    'business_id' => (int) $businessId,
                    'unit_id' => (int) $unitId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($businessUnits !== []) {
            DB::table('business_units')->insert($businessUnits);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('business_units');
        Schema::dropIfExists('business_species');
    }
};
