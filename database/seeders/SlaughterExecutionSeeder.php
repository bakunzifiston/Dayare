<?php

namespace Database\Seeders;

use App\Models\SlaughterExecution;
use App\Models\SlaughterPlan;
use Illuminate\Database\Seeder;

class SlaughterExecutionSeeder extends Seeder
{
    public function run(): void
    {
        $plans = SlaughterPlan::where('status', SlaughterPlan::STATUS_APPROVED)->get();
        if ($plans->isEmpty()) {
            $this->command?->warn('No approved slaughter plans. Run SlaughterPlanSeeder first.');
            return;
        }
        foreach ($plans->take(2) as $plan) {
            SlaughterExecution::firstOrCreate(
                ['slaughter_plan_id' => $plan->id],
                [
                    'actual_animals_slaughtered' => $plan->number_of_animals_scheduled,
                    'slaughter_time' => now()->subDays(2),
                    'status' => SlaughterExecution::STATUS_COMPLETED,
                ]
            );
        }
        $this->command?->info('Slaughter executions seeded.');
    }
}
