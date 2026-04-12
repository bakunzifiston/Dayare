<?php

namespace App\Http\Controllers;

use App\Models\AnimalHealthRecord;
use App\Models\AnimalIntake;
use App\Models\Farm;
use App\Models\Livestock;
use App\Models\SupplyRequest;
use App\Services\Farmer\FarmerSupplyHistoryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FarmerDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $farmerIds = $user->accessibleFarmerBusinessIds();
        $farmIds = Farm::query()->whereIn('business_id', $farmerIds)->pluck('id');

        $totalLivestock = (int) Livestock::query()->whereIn('farm_id', $farmIds)->sum('total_quantity');
        $availableLivestock = (int) Livestock::query()->whereIn('farm_id', $farmIds)->sum('available_quantity');

        $suppliedAnimals = (int) AnimalIntake::query()
            ->where(function ($q) use ($farmIds, $farmerIds) {
                $q->whereIn('farm_id', $farmIds)
                    ->orWhereHas('supplyRequest', fn ($q2) => $q2->whereIn('farmer_id', $farmerIds));
            })
            ->sum('number_of_animals');

        $pendingRequests = SupplyRequest::query()
            ->whereIn('farmer_id', $farmerIds)
            ->where('status', SupplyRequest::STATUS_PENDING)
            ->count();

        $farmsWithLivestock = Farm::query()
            ->whereIn('business_id', $farmerIds)
            ->with('livestock')
            ->orderBy('name')
            ->get();

        $incomingRequests = SupplyRequest::query()
            ->with(['processor', 'destinationFacility'])
            ->whereIn('farmer_id', $farmerIds)
            ->where('status', SupplyRequest::STATUS_PENDING)
            ->latest()
            ->limit(8)
            ->get();

        $recentHealth = AnimalHealthRecord::query()
            ->whereIn('farm_id', $farmIds)
            ->with('farm')
            ->latest('record_date')
            ->limit(8)
            ->get();

        $historyPreview = app(FarmerSupplyHistoryService::class)->history($user, 10);

        return view('farmer.dashboard', compact(
            'user',
            'totalLivestock',
            'availableLivestock',
            'suppliedAnimals',
            'pendingRequests',
            'farmsWithLivestock',
            'incomingRequests',
            'recentHealth',
            'historyPreview'
        ));
    }
}
