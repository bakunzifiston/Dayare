<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\AnimalHealthRecord;
use App\Models\Farm;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FarmerHealthHubController extends Controller
{
    /**
     * All health records across the farmer's farms (sidebar module).
     */
    public function index(Request $request): View
    {
        $farmIds = Farm::query()
            ->whereIn('business_id', $request->user()->accessibleFarmerBusinessIds())
            ->pluck('id');

        $records = AnimalHealthRecord::query()
            ->whereIn('farm_id', $farmIds)
            ->with(['farm', 'livestock'])
            ->latest('record_date')
            ->paginate(25);

        return view('farmer.health.hub', compact('records'));
    }
}
