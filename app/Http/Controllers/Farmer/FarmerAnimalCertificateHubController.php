<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Farmer\Concerns\InteractsWithAccessibleAnimals;
use App\Services\Farmer\AnimalCertificateAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FarmerAnimalCertificateHubController extends Controller
{
    use InteractsWithAccessibleAnimals;

    public function index(Request $request, AnimalCertificateAnalyticsService $analytics): View
    {
        $animalIds = $this->accessibleAnimalIds($request);
        $metrics = $analytics->metrics($animalIds);
        $charts = $analytics->charts($animalIds);

        return view('farmer.animal-certificates.hub', compact('metrics', 'charts'));
    }
}
