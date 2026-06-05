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

    \App\Support\RemovesLegacyBusinessNameUniqueIndexes::remove();

    $this->info('Business name uniqueness indexes removed (if they were still present).');
})->purpose('Remove legacy unique indexes on business names');
