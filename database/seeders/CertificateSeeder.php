<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Certificate;
use App\Models\CertificateQr;
use Illuminate\Database\Seeder;

/**
 * Seed certificates and QR codes for traceability (Rwanda).
 */
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
            $certNum = 'CERT-RW-' . str_pad((string) $counter, 4, '0');
            $cert = Certificate::firstOrCreate(
                ['batch_id' => $batch->id],
                [
                    'certificate_number' => $certNum,
                    'inspector_id' => $batch->inspector_id,
                    'facility_id' => $facility->id,
                    'issued_at' => now(),
                    'expiry_date' => now()->addMonths(6),
                    'status' => Certificate::STATUS_ACTIVE,
                ]
            );
            if (! $cert->certificateQr) {
                $cert->certificateQr()->create(['slug' => CertificateQr::generateSlug()]);
            }
        }
        $this->command?->info('Certificates and QR codes seeded.');
    }
}
