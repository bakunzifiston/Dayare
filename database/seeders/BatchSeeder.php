<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\SlaughterExecution;
use Illuminate\Database\Seeder;

class BatchSeeder extends Seeder
{
    public function run(): void
    {
        $executions = SlaughterExecution::with('slaughterPlan.inspector')->get();
        if ($executions->isEmpty()) {
            $this->command?->warn('No slaughter executions. Run SlaughterExecutionSeeder first.');
            return;
        }

        $counter = 0;
        foreach ($executions as $exec) {
            $inspectorId = $exec->slaughterPlan->inspector_id;
            $qty = (int) min($exec->actual_animals_slaughtered, 20);
            $counter++;
            Batch::firstOrCreate(
                [
                    'slaughter_execution_id' => $exec->id,
                    'batch_code' => 'BAT-TEST-' . str_pad((string) $counter, 3, '0'),
                ],
                [
                    'inspector_id' => $inspectorId,
                    'species' => Batch::SPECIES_CATTLE,
                    'quantity' => $qty,
                    'status' => Batch::STATUS_APPROVED,
                ]
            );
        }

        $this->command?->info('Batches seeded.');
    }
}
