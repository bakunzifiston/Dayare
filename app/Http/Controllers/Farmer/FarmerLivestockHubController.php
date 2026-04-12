<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\Livestock;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FarmerLivestockHubController extends Controller
{
    /**
     * All livestock rows across the farmer's farms (sidebar module).
     */
    public function index(Request $request): View
    {
        $farmIds = Farm::query()
            ->whereIn('business_id', $request->user()->accessibleFarmerBusinessIds())
            ->pluck('id');

        $healthHeadcounts = Livestock::aggregateHealthQuantities(
            Livestock::query()
                ->whereIn('farm_id', $farmIds)
                ->get()
        );

        $rows = Livestock::query()
            ->whereIn('farm_id', $farmIds)
            ->with(['farm', 'detail'])
            ->orderBy('farm_id')
            ->orderBy('type')
            ->orderBy('breed')
            ->paginate(25);

        return view('farmer.livestock.hub', compact('rows', 'healthHeadcounts'));
    }
}
