<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Services\Farmer\SaleAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FarmerSalesHubController extends Controller
{
    public function index(Request $request, SaleAnalyticsService $analytics): View
    {
        $farmIds = Farm::query()
            ->whereIn('business_id', $request->user()->accessibleFarmerBusinessIds())
            ->pluck('id');

        $metrics = $analytics->metrics($farmIds);
        $charts = $analytics->charts($farmIds);

        return view('farmer.sales.hub', compact('metrics', 'charts'));
    }
}
