<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Farmer\Concerns\InteractsWithAccessibleAnimals;
use App\Models\Sale;
use App\Models\SaleAnimal;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleAnimalController extends Controller
{
    use InteractsWithAccessibleAnimals;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Sale::class);

        $animalIds = $this->accessibleAnimalIds($request);
        $query = SaleAnimal::query()
            ->whereIn('animal_id', $animalIds)
            ->with(['sale.buyer', 'sale.farm', 'animal', 'livestock'])
            ->latest();

        if ($search = trim((string) $request->query('q', ''))) {
            $query->whereHas('animal', fn ($q) => $q->where('animal_code', 'like', '%'.$search.'%')
                ->orWhere('tag_number', 'like', '%'.$search.'%'));
        }

        $records = $query->paginate(20)->withQueryString();

        return view('farmer.sales.animals.index', compact('records'));
    }
}
