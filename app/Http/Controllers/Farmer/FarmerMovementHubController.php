<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Services\Farmer\MovementPermitAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FarmerMovementHubController extends Controller
{
    public function index(Request $request, MovementPermitAnalyticsService $analytics): View
    {
        $farmerIds = $request->user()->accessibleFarmerBusinessIds();
        $metrics = $analytics->metrics($farmerIds);
        $charts = $analytics->charts($farmerIds);

        return view('farmer.movement.hub', compact('metrics', 'charts'));
    }
}
