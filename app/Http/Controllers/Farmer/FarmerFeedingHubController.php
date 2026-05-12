<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Farmer\Concerns\InteractsWithAccessibleFarmerBusiness;
use App\Models\FeedInventory;
use App\Services\Farmer\FeedAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FarmerFeedingHubController extends Controller
{
    use InteractsWithAccessibleFarmerBusiness;

    public function index(Request $request, FeedAnalyticsService $analytics): View
    {
        $businessIds = $this->accessibleBusinessIds($request);
        $metrics = $analytics->metrics($businessIds);
        $charts = $analytics->charts($businessIds);

        $feedTypeIds = $this->accessibleFeedTypesQuery($request)->pluck('id');
        $lowStock = FeedInventory::query()
            ->whereIn('feed_type_id', $feedTypeIds)
            ->whereIn('status', [FeedInventory::STATUS_LOW_STOCK, FeedInventory::STATUS_OUT_OF_STOCK])
            ->with('feedType')
            ->orderBy('quantity_remaining')
            ->limit(10)
            ->get();

        $expiring = FeedInventory::query()
            ->whereIn('feed_type_id', $feedTypeIds)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays(14))
            ->with('feedType')
            ->orderBy('expiry_date')
            ->limit(10)
            ->get();

        return view('farmer.feeding.hub', compact('metrics', 'charts', 'lowStock', 'expiring'));
    }
}
