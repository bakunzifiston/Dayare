<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\AnimalCertificate;
use App\Models\AnimalHealthRecord;
use App\Models\Farm;
use App\Models\Livestock;
use App\Models\LivestockEvent;
use App\Models\MortalityRecord;
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

        $animalIds = Animal::query()
            ->whereHas('livestock.farm', fn ($query) => $query->whereIn('business_id', $farmerIds))
            ->pluck('id');
        $totalAnimals = $animalIds->count();

        $today = Carbon::today();
        $activeCertificates = AnimalCertificate::query()
            ->whereIn('animal_id', $animalIds)
            ->where('certificate_status', AnimalCertificate::STATUS_ACTIVE)
            ->whereDate('issue_date', '<=', $today)
            ->where(function ($query) use ($today): void {
                $query->whereNull('expiry_date')
                    ->orWhereDate('expiry_date', '>=', $today);
            })
            ->get(['id', 'animal_id', 'certificate_type']);

        $certifiedAnimalIds = $activeCertificates
            ->pluck('animal_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique();
        $certifiedAnimalCount = $certifiedAnimalIds->count();
        $traceabilityAnimalIds = $activeCertificates
            ->where('certificate_type', AnimalCertificate::TYPE_TRACEABILITY)
            ->pluck('animal_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique();
        $traceabilityAnimalCount = $traceabilityAnimalIds->count();
        $mortalityCount = (int) MortalityRecord::query()->whereIn('animal_id', $animalIds)->count();

        $healthyPercent = $this->percentage($healthyLivestock, $totalLivestock);
        $sickPercent = $this->percentage($sickLivestock, $totalLivestock);
        $mortalityRatePercent = $this->percentage($mortalityCount, max(1, $totalAnimals));
        $complianceStatusPercent = $this->percentage($certifiedAnimalCount, max(1, $totalAnimals));
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

        $passportCoveragePercent = $this->percentage($traceabilityAnimalCount, max(1, $totalAnimals));

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

        $recentHealth = AnimalHealthRecord::query()
            ->whereIn('farm_id', $farmIds)
            ->with('farm')
            ->latest('record_date')
            ->limit(8)
            ->get();

        return view('farmer.dashboard', compact(
            'user',
            'totalLivestock',
            'availableLivestock',
            'healthyLivestock',
            'sickLivestock',
            'healthyPercent',
            'sickPercent',
            'mortalityRatePercent',
            'mortalityCount',
            'complianceStatusPercent',
            'certifiedAnimalCount',
            'totalAnimals',
            'animalsPerSpecies',
            'newAnimals',
            'soldAnimals',
            'deadAnimals',
            'netGrowthAnimals',
            'growthRatePercent',
            'stockDistributionByFarm',
            'passportCoveragePercent',
            'traceabilityAnimalCount',
            'weightedAge',
            'weightedWeight',
            'farmsWithLivestock',
            'recentHealth'
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
