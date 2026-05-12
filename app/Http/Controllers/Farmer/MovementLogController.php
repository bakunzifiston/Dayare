<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\MovementLog;
use App\Models\MovementPermit;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MovementLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', MovementPermit::class);
        $farmerIds = $request->user()->accessibleFarmerBusinessIds();

        $records = MovementLog::query()
            ->whereHas('movementPermit', fn ($q) => $q->whereIn('farmer_id', $farmerIds))
            ->with(['movementPermit', 'actor'])
            ->latest('action_date')
            ->paginate(30)
            ->withQueryString();

        return view('farmer.movement.logs.index', compact('records'));
    }
}
