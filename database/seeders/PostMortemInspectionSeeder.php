<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\PostMortemInspection;
use Illuminate\Database\Seeder;

class PostMortemInspectionSeeder extends Seeder
{
    public function run(): void
    {
        $batches = Batch::with('inspector')->get();
        if ($batches->isEmpty()) {
            $this->command?->warn('No batches. Run BatchSeeder first.');
            return;
        }
        foreach ($batches as $batch) {
            PostMortemInspection::firstOrCreate(
                ['batch_id' => $batch->id],
                [
                    'inspector_id' => $batch->inspector_id,
                    'total_examined' => $batch->quantity,
                    'approved_quantity' => $batch->quantity,
                    'condemned_quantity' => 0,
                    'notes' => 'Test post-mortem inspection.',
                    'inspection_date' => now()->subDay(),
                ]
            );
        }
        $this->command?->info('Post-mortem inspections seeded.');
    }
}
