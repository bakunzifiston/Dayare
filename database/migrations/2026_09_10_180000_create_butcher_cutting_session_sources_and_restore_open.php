<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('butcher_cutting_session_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('butcher_cutting_sessions')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('butcher_inventory_batches')->cascadeOnDelete();
            $table->decimal('source_weight_kg', 12, 3);
            $table->timestamps();

            $table->unique(['session_id', 'batch_id']);
            $table->index(['batch_id']);
        });

        $this->backfillSourcesAndRestoreOpenSessions();
    }

    public function down(): void
    {
        Schema::dropIfExists('butcher_cutting_session_sources');
    }

    private function backfillSourcesAndRestoreOpenSessions(): void
    {
        $openRestored = 0;

        DB::table('butcher_cutting_sessions')->orderBy('id')->chunkById(100, function ($sessions) use (&$openRestored): void {
            foreach ($sessions as $session) {
                if ($session->batch_id === null) {
                    continue;
                }

                $exists = DB::table('butcher_cutting_session_sources')
                    ->where('session_id', $session->id)
                    ->where('batch_id', $session->batch_id)
                    ->exists();

                if (! $exists) {
                    DB::table('butcher_cutting_session_sources')->insert([
                        'session_id' => $session->id,
                        'batch_id' => $session->batch_id,
                        'source_weight_kg' => $session->source_weight_kg,
                        'created_at' => $session->created_at,
                        'updated_at' => $session->updated_at,
                    ]);
                }

                // Phase 4: open sessions previously deducted at open — restore that weight.
                if ((string) $session->status === 'open') {
                    $batch = DB::table('butcher_inventory_batches')->where('id', $session->batch_id)->first();
                    if ($batch === null) {
                        continue;
                    }

                    $restored = round((float) $batch->remaining_weight_kg + (float) $session->source_weight_kg, 3);
                    $status = $restored <= 0
                        ? 'fully_used'
                        : (in_array((string) $batch->status, ['disposed', 'expired'], true)
                            ? $batch->status
                            : 'in_storage');

                    // Prefer partially_used when some of initial was already used historically.
                    if ($status === 'in_storage' && $restored + 0.001 < (float) $batch->initial_weight_kg) {
                        $status = 'partially_used';
                    }

                    DB::table('butcher_inventory_batches')->where('id', $batch->id)->update([
                        'remaining_weight_kg' => $restored,
                        'status' => $status,
                        'updated_at' => now(),
                    ]);

                    $openRestored++;
                }
            }
        });

        if ($openRestored > 0) {
            logger()->warning('butcher.phase4.restored_open_cutting_sessions', [
                'count' => $openRestored,
                'note' => 'Open sessions had source weight restored to batches. Review before closing.',
            ]);
        }
    }
};
