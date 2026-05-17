<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\MovementHistory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MovementHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $farmerIds = $request->user()->accessibleFarmerBusinessIds();

        $query = MovementHistory::query()
            ->whereHas('permit', fn ($q) => $q->whereIn('farmer_id', $farmerIds))
            ->with(['animal', 'permit', 'sourceFarm', 'recorder'])
            ->latest('movement_date');

        if ($status = (string) $request->query('status', '')) {
            $query->where('status', $status);
        }

        $records = $query->paginate(25)->withQueryString();

        return view('farmer.movement.history.index', compact('records'));
    }

    public function show(Request $request, MovementHistory $movementHistory): View
    {
        $farmerIds = $request->user()->accessibleFarmerBusinessIds();
        abort_unless($movementHistory->permit && $farmerIds->contains((int) $movementHistory->permit->farmer_id), 403);

        $movementHistory->load(['animal.livestock.farm', 'permit', 'sourceFarm', 'recorder']);

        return view('farmer.movement.history.show', ['record' => $movementHistory]);
    }
}
