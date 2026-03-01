<?php

namespace Database\Seeders;

use App\Models\AnteMortemInspection;
use App\Models\SlaughterPlan;
use Illuminate\Database\Seeder;

class AnteMortemInspectionSeeder extends Seeder
{
    public function run(): void
    {
        $plans = SlaughterPlan::with('inspector')->get();
        if ($plans->isEmpty()) {
            $this->command?->warn('No slaughter plans. Run SlaughterPlanSeeder first.');
            return;
        }

        foreach ($plans->take(3) as $plan) {
            AnteMortemInspection::firstOrCreate(
                [
                    'slaughter_plan_id' => $plan->id,
                    'species' => $plan->species,
                ],
                [
                    'inspector_id' => $plan->inspector_id,
                    'number_examined' => $plan->number_of_animals_scheduled,
                    'number_approved' => $plan->number_of_animals_scheduled,
                    'number_rejected' => 0,
                    'notes' => 'Test ante-mortem inspection.',
                    'inspection_date' => $plan->slaughter_date,
                ]
            );
        }

        $this->command?->info('Ante-mortem inspections seeded.');
    }
}
