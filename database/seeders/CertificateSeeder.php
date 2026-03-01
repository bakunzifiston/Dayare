<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Certificate;
use Illuminate\Database\Seeder;

class CertificateSeeder extends Seeder
{
    public function run(): void
    {
        $batches = Batch::with(['inspector', 'slaughterExecution.slaughterPlan.facility'])->where('status', 'approved')->get();
        if ($batches->isEmpty()) {
            $this->command?->warn('No approved batches. Run BatchSeeder first.');
            return;
        }
        $counter = 0;
        foreach ($batches as $batch) {
            $pm = $batch->postMortemInspection;
            if (! $pm || $pm->approved_quantity <= 0) {
                continue;
            }
            $exec = $batch->slaughterExecution;
            $plan = $exec ? $exec->slaughterPlan : null;
            $facility = $plan ? $plan->facility : null;
            if (! $facility) {
                continue;
            }
            $counter++;
            $certNum = 'CERT-TEST-' . str_pad((string) $counter, 4, '0');
            Certificate::firstOrCreate(
                ['certificate_number' => $certNum],
                [
                    'batch_id' => $batch->id,
                    'inspector_id' => $batch->inspector_id,
                    'facility_id' => $facility->id,
                    'issued_at' => now(),
                    'expiry_date' => now()->addMonths(6),
                    'status' => Certificate::STATUS_ACTIVE,
                ]
            );
        }
        $this->command?->info('Certificates seeded.');
    }
}
