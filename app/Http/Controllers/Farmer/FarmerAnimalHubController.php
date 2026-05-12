<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FarmerAnimalHubController extends Controller
{
    public function index(Request $request): View
    {
        $farmerIds = $request->user()->accessibleFarmerBusinessIds();

        $baseQuery = Animal::query()
            ->whereHas('livestock.farm', fn ($query) => $query->whereIn('business_id', $farmerIds));

        $query = (clone $baseQuery)->with(['livestock.farm']);

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('animal_code', 'like', '%'.$search.'%')
                    ->orWhere('tag_number', 'like', '%'.$search.'%')
                    ->orWhere('animal_name', 'like', '%'.$search.'%');
            });
        }

        foreach (['health_status', 'lifecycle_status', 'gender'] as $filter) {
            $value = (string) $request->query($filter, '');
            if ($value !== '') {
                $query->where($filter, $value);
            }
        }

        $animals = $query->latest()->paginate(12)->withQueryString();

        $stats = [
            'total' => (int) (clone $baseQuery)->count(),
            'active' => (int) (clone $baseQuery)->where('lifecycle_status', Animal::LIFECYCLE_ACTIVE)->count(),
            'sick' => (int) (clone $baseQuery)->where('health_status', Animal::HEALTH_SICK)->count(),
            'ready_for_sale' => (int) (clone $baseQuery)->where('production_status', Animal::PRODUCTION_READY_FOR_SALE)->count(),
        ];

        return view('farmer.animals.hub', compact('animals', 'stats'));
    }
}
