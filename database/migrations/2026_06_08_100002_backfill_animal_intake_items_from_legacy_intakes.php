<?php

use App\Support\LegacyAnimalIntakeBackfill;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Expand legacy group-based intakes into per-animal item rows.
     * Idempotent: skips intakes that already have items.
     */
    public function up(): void
    {
        (new LegacyAnimalIntakeBackfill)->run();
    }

    public function down(): void
    {
        DB::table('animal_intake_items')
            ->where('ear_tag', 'like', 'LEGACY-%')
            ->delete();
    }
};
