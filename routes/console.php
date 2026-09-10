<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('intake:backfill', function () {
    $result = (new \App\Support\LegacyAnimalIntakeBackfill)->run();

    if ($result['intakes_processed'] === 0) {
        $this->info('No legacy intakes without item records were found.');
    } else {
        $this->info(sprintf(
            'Backfilled %d animal item record(s) across %d intake(s).',
            $result['items_created'],
            $result['intakes_processed'],
        ));
    }

    $assignResult = app(\App\Services\Processor\SlaughterPlanAssignmentService::class)->assignOrphanedPlans();

    if ($assignResult['plans_assigned'] === 0) {
        $this->info('No slaughter plans needed animal assignment.');

        return;
    }

    $this->info(sprintf(
        'Assigned %d animal(s) across %d slaughter plan(s).',
        $assignResult['items_assigned'],
        $assignResult['plans_assigned'],
    ));

    if ($assignResult['plans_skipped'] > 0) {
        $this->warn(sprintf(
            '%d plan(s) could not be fully assigned — not enough available animals.',
            $assignResult['plans_skipped'],
        ));
    }
})->purpose('Generate per-animal item records for legacy group-based intakes');

Artisan::command('slaughter-plan:assign', function () {
    $result = app(\App\Services\Processor\SlaughterPlanAssignmentService::class)->assignOrphanedPlans();

    if ($result['plans_assigned'] === 0) {
        $this->info('No slaughter plans needed animal assignment.');

        return;
    }

    $this->info(sprintf(
        'Assigned %d animal(s) across %d slaughter plan(s).',
        $result['items_assigned'],
        $result['plans_assigned'],
    ));

    if ($result['plans_skipped'] > 0) {
        $this->warn(sprintf(
            '%d plan(s) could not be fully assigned — not enough available animals.',
            $result['plans_skipped'],
        ));
    }
})->purpose('Assign per-animal items to slaughter plans missing assignments');

Artisan::command('businesses:drop-name-uniqueness', function () {
    if (! Schema::hasTable('businesses')) {
        $this->warn('The businesses table does not exist.');

        return;
    }

    \App\Support\RemovesLegacyBusinessNameUniqueIndexes::remove();

    $this->info('Business name uniqueness indexes removed (if they were still present).');
})->purpose('Remove legacy unique indexes on business names');

Artisan::command('butcher:reconcile-inventory {--business=} {--fail-on-mismatch}', function () {
    $businessId = $this->option('business');
    $business = null;
    if ($businessId !== null && $businessId !== '') {
        $business = \App\Models\Business::query()->findOrFail((int) $businessId);
    }

    $mismatches = app(\App\Services\Butcher\ButcherInventoryReconciliationService::class)
        ->findMismatches($business);

    if ($mismatches->isEmpty()) {
        $this->info('No butcher inventory ledger mismatches found.');

        return 0;
    }

    $this->warn(sprintf('Found %d batch mismatch(es) — review only; no auto-correction applied.', $mismatches->count()));
    $this->table(
        ['Batch ID', 'Batch #', 'Remaining kg', 'Ledger sum kg', 'Delta kg'],
        $mismatches->map(fn (array $row) => [
            $row['batch_id'],
            $row['batch_number'],
            $row['remaining_weight_kg'],
            $row['ledger_sum_kg'],
            $row['delta_kg'],
        ])->all()
    );

    return $this->option('fail-on-mismatch') ? 1 : 0;
})->purpose('Flag butcher batches whose remaining weight does not match ledger sums');

Artisan::command('butcher:backfill-inventory-ledger {--business=}', function () {
    $businessId = $this->option('business');
    $business = null;
    if ($businessId !== null && $businessId !== '') {
        $business = \App\Models\Business::query()->findOrFail((int) $businessId);
    }

    $result = app(\App\Services\Butcher\ButcherInventoryReconciliationService::class)
        ->backfillMissingLedger($business);

    $this->info(sprintf(
        'Backfilled ledger for %d batch(es); created %d movement row(s).',
        $result['batches_backfilled'],
        $result['movements_created'],
    ));
})->purpose('Backfill receipt/disposal/adjustment/cutting ledger rows for pre-Phase-3 batches');

Artisan::command('butcher:review-open-cutting-sessions {--business=}', function () {
    $query = \App\Models\ButcherCuttingSession::query()
        ->with(['batch', 'sources.batch', 'business'])
        ->where('status', \App\Models\ButcherCuttingSession::STATUS_OPEN)
        ->orderBy('id');

    if ($this->option('business')) {
        $query->where('business_id', (int) $this->option('business'));
    }

    $sessions = $query->get();
    if ($sessions->isEmpty()) {
        $this->info('No open cutting sessions found.');

        return 0;
    }

    $this->warn(sprintf(
        'Found %d open cutting session(s). Under Phase 4 these should not hold deducted inventory — review before closing.',
        $sessions->count()
    ));

    $this->table(
        ['Session', 'Business', 'Status', 'Source kg', 'Sources', 'Primary batch'],
        $sessions->map(fn ($s) => [
            $s->session_number,
            $s->business_id,
            $s->status,
            $s->source_weight_kg,
            $s->sources->count(),
            $s->batch?->batch_number,
        ])->all()
    );

    return 0;
})->purpose('List open cutting sessions for Phase 4 manual review');
