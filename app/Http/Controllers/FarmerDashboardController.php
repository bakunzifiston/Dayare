<?php

namespace App\Http\Controllers;

use App\Models\AnimalHealthRecord;
use App\Models\AnimalIntake;
use App\Models\Farm;
use App\Models\FarmerHealthCertificate;
use App\Models\Livestock;
use App\Models\LivestockEvent;
use App\Models\SupplyRequest;
use App\Services\Farmer\FarmerSupplyHistoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class FarmerDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $farmerIds = $user->accessibleFarmerBusinessIds();
        $farmIds = Farm::query()->whereIn('business_id', $farmerIds)->pluck('id');
        $livestockBaseQuery = Livestock::query()->whereIn('farm_id', $farmIds);

        $totalLivestock = (int) (clone $livestockBaseQuery)->sum('total_quantity');
        $availableLivestock = (int) (clone $livestockBaseQuery)->sum('available_quantity');
        $healthyLivestock = (int) (clone $livestockBaseQuery)->sum('healthy_quantity');
        $sickLivestock = (int) (clone $livestockBaseQuery)->sum('sick_quantity');

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
        $activeSupplyRequests = SupplyRequest::query()
            ->whereIn('farmer_id', $farmerIds)
            ->whereIn('status', [SupplyRequest::STATUS_PENDING, SupplyRequest::STATUS_ACCEPTED])
            ->count();
        $totalSupplyRequests = SupplyRequest::query()
            ->whereIn('farmer_id', $farmerIds)
            ->count();
        $fulfilledSupplyRequests = SupplyRequest::query()
            ->whereIn('farmer_id', $farmerIds)
            ->where('status', SupplyRequest::STATUS_FULFILLED)
            ->count();

        $supplyRevenue = (float) AnimalIntake::query()
            ->whereHas('supplyRequest', fn ($q) => $q->whereIn('farmer_id', $farmerIds))
            ->selectRaw('SUM(COALESCE(total_price, (unit_price * number_of_animals), 0)) as revenue_total')
            ->value('revenue_total');

        $today = Carbon::today();
        $validCertificates = FarmerHealthCertificate::query()
            ->whereIn('farmer_id', $farmerIds)
            ->where('status', FarmerHealthCertificate::STATUS_VALID)
            ->whereDate('issue_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('expiry_date')
                    ->orWhereDate('expiry_date', '>=', $today);
            })
            ->get(['id', 'farm_id', 'livestock_id']);

        $certifiedLivestockIds = $validCertificates
            ->pluck('livestock_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();
        $farmScopedCertificateIds = $validCertificates
            ->whereNull('livestock_id')
            ->pluck('farm_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();
        $farmScopedLivestockIds = $farmScopedCertificateIds->isEmpty()
            ? collect()
            : Livestock::query()
                ->whereIn('farm_id', $farmScopedCertificateIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id);
        $allCertifiedLivestockIds = $certifiedLivestockIds
            ->merge($farmScopedLivestockIds)
            ->unique()
            ->values();
        $certifiedLivestockCount = $allCertifiedLivestockIds->isEmpty()
            ? 0
            : (int) Livestock::query()
                ->whereIn('id', $allCertifiedLivestockIds)
                ->sum('total_quantity');

        $healthyPercent = $this->percentage($healthyLivestock, $totalLivestock);
        $sickPercent = $this->percentage($sickLivestock, $totalLivestock);
        $mortalityRatePercent = 0.0; // Mortality events are not currently tracked explicitly.
        $fulfilledSupplyRatePercent = $this->percentage($fulfilledSupplyRequests, $totalSupplyRequests);
        $complianceStatusPercent = $this->percentage($certifiedLivestockCount, $totalLivestock);
        $animalsPerSpecies = (clone $livestockBaseQuery)
            ->selectRaw('type, SUM(total_quantity) as total_animals')
            ->groupBy('type')
            ->orderByDesc('total_animals')
            ->get()
            ->map(fn ($row) => [
                'type' => (string) $row->type,
                'total' => (int) $row->total_animals,
                'share_percent' => $this->percentage((int) $row->total_animals, $totalLivestock),
            ]);

        $windowStart = Carbon::today()->subDays(30);
        $newAnimals = (int) Livestock::query()
            ->whereIn('farm_id', $farmIds)
            ->whereDate('created_at', '>=', $windowStart)
            ->sum('total_quantity');
        $soldAnimals = (int) LivestockEvent::query()
            ->whereIn('farm_id', $farmIds)
            ->where('event_type', LivestockEvent::TYPE_SUPPLY_FULFILLMENT)
            ->whereDate('event_date', '>=', $windowStart)
            ->sum('quantity');
        $deadAnimals = (int) LivestockEvent::query()
            ->whereIn('farm_id', $farmIds)
            ->where('event_type', 'mortality')
            ->whereDate('event_date', '>=', $windowStart)
            ->sum('quantity');
        $netGrowthAnimals = $newAnimals - $soldAnimals - $deadAnimals;
        $growthRatePercent = $this->percentage($netGrowthAnimals, max(1, $totalLivestock));

        $breedCertificates = $validCertificates->where('certificate_type', FarmerHealthCertificate::TYPE_BREED)->values();
        $breedCertificateLivestockIds = $breedCertificates
            ->pluck('livestock_id')
            ->filter()
            ->map(fn ($id) => (int) $id);
        $breedCertFarmIds = $breedCertificates
            ->whereNull('livestock_id')
            ->pluck('farm_id')
            ->filter()
            ->map(fn ($id) => (int) $id);
        $breedCertFarmLivestockIds = $breedCertFarmIds->isEmpty()
            ? collect()
            : Livestock::query()
                ->whereIn('farm_id', $breedCertFarmIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id);
        $breedCertifiedLivestockIds = $breedCertificateLivestockIds
            ->merge($breedCertFarmLivestockIds)
            ->unique()
            ->values();
        $breedCertifiedAnimals = $breedCertifiedLivestockIds->isEmpty()
            ? 0
            : (int) Livestock::query()
                ->whereIn('id', $breedCertifiedLivestockIds)
                ->sum('total_quantity');
        $breedingRatePercent = $this->percentage($breedCertifiedAnimals, $totalLivestock);

        $farmsWithLivestock = Farm::query()
            ->whereIn('business_id', $farmerIds)
            ->with('livestock.detail')
            ->orderBy('name')
            ->get();
        $stockDistributionByFarm = $farmsWithLivestock->map(function (Farm $farm) use ($totalLivestock) {
            $farmTotal = (int) $farm->livestock->sum('total_quantity');

            return [
                'farm' => $farm,
                'total' => $farmTotal,
                'available' => (int) $farm->livestock->sum('available_quantity'),
                'share_percent' => $this->percentage($farmTotal, $totalLivestock),
            ];
        })->sortByDesc('total')->values();

        $weightedAge = $this->computeWeightedRangeAverage(
            $farmsWithLivestock->flatMap(fn (Farm $farm) => $farm->livestock),
            'age_range'
        );
        $weightedWeight = $this->computeWeightedRangeAverage(
            $farmsWithLivestock->flatMap(fn (Farm $farm) => $farm->livestock),
            'weight_range'
        );

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
            'healthyLivestock',
            'sickLivestock',
            'healthyPercent',
            'sickPercent',
            'mortalityRatePercent',
            'activeSupplyRequests',
            'fulfilledSupplyRatePercent',
            'supplyRevenue',
            'complianceStatusPercent',
            'certifiedLivestockCount',
            'animalsPerSpecies',
            'newAnimals',
            'soldAnimals',
            'deadAnimals',
            'netGrowthAnimals',
            'growthRatePercent',
            'stockDistributionByFarm',
            'breedingRatePercent',
            'breedCertifiedAnimals',
            'weightedAge',
            'weightedWeight',
            'farmsWithLivestock',
            'incomingRequests',
            'recentHealth',
            'historyPreview'
        ));
    }

    private function percentage(int $numerator, int $denominator): float
    {
        if ($denominator <= 0) {
            return 0.0;
        }

        return round(($numerator / $denominator) * 100, 2);
    }

    private function computeWeightedRangeAverage(Collection $livestockRows, string $rangeField): ?float
    {
        $weightedTotal = 0.0;
        $totalWeight = 0;

        foreach ($livestockRows as $row) {
            $rangeValue = (string) data_get($row, "detail.{$rangeField}", '');
            $avgValue = $this->extractAverageFromRange($rangeValue);
            if ($avgValue === null) {
                continue;
            }

            $quantity = max(1, (int) $row->total_quantity);
            $weightedTotal += $avgValue * $quantity;
            $totalWeight += $quantity;
        }

        if ($totalWeight === 0) {
            return null;
        }

        return round($weightedTotal / $totalWeight, 2);
    }

    private function extractAverageFromRange(?string $value): ?float
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        preg_match_all('/\d+(?:\.\d+)?/', $value, $matches);
        $numbers = array_map('floatval', $matches[0] ?? []);
        if ($numbers === []) {
            return null;
        }

        return round(array_sum($numbers) / count($numbers), 2);
    }
}
