<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\AnimalHealthRecord;
use App\Models\Farm;
use App\Models\Livestock;
use Carbon\Carbon;
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

        $today = Carbon::today();
        $upcomingVaccinations = AnimalHealthRecord::query()
            ->whereIn('farm_id', $farmIds)
            ->where('event_type', AnimalHealthRecord::EVENT_VACCINATION)
            ->whereDate('next_due_date', '>=', $today)
            ->whereDate('next_due_date', '<=', $today->copy()->addDays(14))
            ->with(['farm', 'livestock'])
            ->orderBy('next_due_date')
            ->limit(15)
            ->get();

        $sickRows = Livestock::query()
            ->whereIn('farm_id', $farmIds)
            ->where('sick_quantity', '>', 0)
            ->with('farm')
            ->orderByDesc('sick_quantity')
            ->limit(15)
            ->get();

        return view('farmer.health.hub', compact('records', 'upcomingVaccinations', 'sickRows'));
    }
}
