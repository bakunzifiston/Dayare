<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\Inspector;
use App\Models\SlaughterPlan;
use Illuminate\Database\Seeder;

class SlaughterPlanSeeder extends Seeder
{
    public function run(): void
    {
        $facilities = Facility::with('inspectors')->has('inspectors')->get();
        if ($facilities->isEmpty()) {
            $this->command?->warn('No facilities with inspectors. Run TestDataSeeder first.');
            return;
        }

        foreach ($facilities->take(3) as $facility) {
            $inspector = $facility->inspectors->first();
            if (! $inspector) {
                continue;
            }
            $slaughterDate = now()->addDays(7)->format('Y-m-d');
            SlaughterPlan::firstOrCreate(
                [
                    'facility_id' => $facility->id,
                    'slaughter_date' => $slaughterDate,
                ],
                [
                    'inspector_id' => $inspector->id,
                    'species' => SlaughterPlan::SPECIES_CATTLE,
                    'number_of_animals_scheduled' => 15,
                    'status' => SlaughterPlan::STATUS_APPROVED,
                ]
            );
        }

        $this->command?->info('Slaughter plans seeded.');
    }
}
