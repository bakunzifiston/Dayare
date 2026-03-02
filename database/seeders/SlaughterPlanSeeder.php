<?php

namespace Database\Seeders;

use App\Models\AnimalIntake;
use App\Models\SlaughterPlan;
use Illuminate\Database\Seeder;

/**
 * Seed slaughter plans linked to animal intakes (Rwanda). Requires AnimalIntakeSeeder first.
 */
class SlaughterPlanSeeder extends Seeder
{
    public function run(): void
    {
        $intakes = AnimalIntake::with('facility.inspectors')
            ->where('status', AnimalIntake::STATUS_APPROVED)
            ->get()
            ->filter(fn (AnimalIntake $i) => ! $i->isHealthCertificateExpired() && $i->remainingAnimalsAvailable() > 0);

        if ($intakes->isEmpty()) {
            $this->command?->warn('No approved animal intakes with valid health cert. Run AnimalIntakeSeeder first.');
            return;
        }

        $slaughterDate = now()->addDays(5)->format('Y-m-d');
        foreach ($intakes->take(4) as $intake) {
            $inspector = $intake->facility->inspectors->first();
            if (! $inspector) {
                continue;
            }
            $num = min($intake->remainingAnimalsAvailable(), rand(5, 15));
            if ($num < 1) {
                continue;
            }
            SlaughterPlan::firstOrCreate(
                [
                    'facility_id' => $intake->facility_id,
                    'animal_intake_id' => $intake->id,
                    'slaughter_date' => $slaughterDate,
                ],
                [
                    'inspector_id' => $inspector->id,
                    'species' => $intake->species,
                    'number_of_animals_scheduled' => $num,
                    'status' => SlaughterPlan::STATUS_APPROVED,
                ]
            );
        }

        $this->command?->info('Slaughter plans seeded (linked to Rwanda animal intakes).');
    }
}
