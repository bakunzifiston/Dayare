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
