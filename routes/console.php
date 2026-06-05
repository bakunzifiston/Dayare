<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('businesses:drop-name-uniqueness', function () {
    if (! Schema::hasTable('businesses')) {
        $this->warn('The businesses table does not exist.');

        return;
    }

    Artisan::call('migrate', [
        '--force' => true,
        '--path' => 'database/migrations/2026_05_21_120000_drop_global_business_name_unique_constraints.php',
    ]);
    $this->line(trim(Artisan::output()));

    Artisan::call('migrate', [
        '--force' => true,
        '--path' => 'database/migrations/2026_05_23_100000_ensure_business_name_indexes_removed.php',
    ]);
    $this->line(trim(Artisan::output()));

    $this->info('Business name uniqueness indexes removed (if they were still present).');
})->purpose('Remove legacy unique indexes on business names');
