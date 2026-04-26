<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Business;
use App\Models\Species;
use App\Models\Unit;

$species = Species::all();
$units = Unit::all();

Business::all()->each(function($b) use ($species, $units) {
    $b->species()->syncWithoutDetaching($species->pluck('id'));
    $b->units()->syncWithoutDetaching($units->pluck('id'));
    echo "Associated species/units for business: {$b->business_name}\n";
});
