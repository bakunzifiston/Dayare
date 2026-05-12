<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Farmer\Concerns\InteractsWithAccessibleAnimals;
use App\Models\MovementPermit;
use App\Models\MovementPermitAnimal;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MovementAnimalController extends Controller
{
    use InteractsWithAccessibleAnimals;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', MovementPermit::class);
        $animalIds = $this->accessibleAnimalIds($request);

        $records = MovementPermitAnimal::query()
            ->whereIn('animal_id', $animalIds)
            ->with(['movementPermit.sourceFarm', 'animal'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('farmer.movement.animals.index', compact('records'));
    }
}
