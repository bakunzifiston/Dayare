<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Farmer\Concerns\InteractsWithAccessibleAnimals;
use App\Services\Farmer\AnimalHealthTimelineService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HealthTimelineController extends Controller
{
    use InteractsWithAccessibleAnimals;

    public function index(Request $request, AnimalHealthTimelineService $timeline): View
    {
        $animals = $this->accessibleAnimalsQuery($request)->orderBy('animal_code')->get();
        $selectedAnimal = null;
        $events = collect();

        if ($animalId = (int) $request->query('animal_id', 0)) {
            $selectedAnimal = $this->findAccessibleAnimal($request, $animalId);
            if ($selectedAnimal) {
                $events = $timeline->forAnimal($selectedAnimal);
            }
        }

        return view('farmer.health.timeline.index', compact('animals', 'selectedAnimal', 'events'));
    }
}
