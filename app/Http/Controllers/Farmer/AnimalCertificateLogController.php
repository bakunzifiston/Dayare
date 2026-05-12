<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Farmer\Concerns\InteractsWithAccessibleAnimals;
use App\Models\AnimalCertificateLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnimalCertificateLogController extends Controller
{
    use InteractsWithAccessibleAnimals;

    public function index(Request $request): View
    {
        $animalIds = $this->accessibleAnimalIds($request);

        $logs = AnimalCertificateLog::query()
            ->whereHas('certificate', fn ($query) => $query->whereIn('animal_id', $animalIds))
            ->with(['certificate.animal', 'actor'])
            ->latest('action_date')
            ->paginate(25);

        return view('farmer.animal-certificates.logs.index', compact('logs'));
    }
}
