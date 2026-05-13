<?php

namespace App\Services\Farmer;

use App\Models\Animal;
use App\Models\AnimalCertificate;
use App\Models\AnimalCertificateLog;
use App\Models\Buyer;
use App\Models\DiseaseRecord;
use App\Models\Farm;
use App\Models\FeedInventory;
use App\Models\Livestock;
use App\Models\MovementLog;
use App\Models\MovementPermit;
use App\Models\MortalityRecord;
use App\Models\Sale;
use App\Models\User;
use App\Models\Vaccination;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FarmerDashboardService
{
    public function __construct(
        private readonly HealthDashboardService $healthDashboard,
        private readonly FeedAnalyticsService $feedAnalytics,
        private readonly SaleAnalyticsService $saleAnalytics,
        private readonly MovementPermitAnalyticsService $movementAnalytics,
        private readonly AnimalCertificateAnalyticsService $certificateAnalytics,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        $farmerIds = $user->accessibleFarmerBusinessIds();
        $farmIds = Farm::query()->whereIn('business_id', $farmerIds)->pluck('id');
        $animalIds = Animal::query()
            ->whereHas('livestock.farm', fn ($query) => $query->whereIn('business_id', $farmerIds))
            ->pluck('id');
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();

        $health = $this->healthDashboard->metrics($animalIds);
        $feeding = $this->feedAnalytics->metrics($farmerIds);
        $sales = $this->saleAnalytics->metrics($farmIds);
        $movement = $this->movementAnalytics->metrics($farmerIds);
        $certificates = $this->certificateAnalytics->metrics($animalIds);

        $activeAnimals = (int) Animal::query()
            ->whereIn('id', $animalIds)
            ->where('lifecycle_status', Animal::LIFECYCLE_ACTIVE)
            ->count();
        $soldAnimals = (int) Animal::query()
            ->whereIn('id', $animalIds)
            ->where('lifecycle_status', Animal::LIFECYCLE_SOLD)
            ->count();
        $quarantinedAnimals = (int) Animal::query()
            ->whereIn('id', $animalIds)
            ->where('health_status', Animal::HEALTH_QUARANTINED)
            ->count();
        $healthyAnimals = (int) Animal::query()
            ->whereIn('id', $animalIds)
            ->where('health_status', Animal::HEALTH_HEALTHY)
            ->count();

        $vaccinationsDueToday = (int) Vaccination::query()
            ->whereIn('animal_id', $animalIds)
            ->whereDate('next_due_date', $today)
            ->whereIn('status', [Vaccination::STATUS_SCHEDULED, Vaccination::STATUS_COMPLETED])
            ->count();

        $expiredCertificates = (int) AnimalCertificate::query()
            ->whereIn('animal_id', $animalIds)
            ->where(function ($query) use ($today): void {
                $query->where('certificate_status', AnimalCertificate::STATUS_EXPIRED)
                    ->orWhere(function ($inner) use ($today): void {
                        $inner->where('certificate_status', AnimalCertificate::STATUS_ACTIVE)
                            ->whereNotNull('expiry_date')
                            ->whereDate('expiry_date', '<', $today);
                    });
            })
            ->count();

        $certificatesExpiringSoon = (int) AnimalCertificate::query()
            ->whereIn('animal_id', $animalIds)
            ->where('certificate_status', AnimalCertificate::STATUS_ACTIVE)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$today, $today->copy()->addDays(30)])
            ->count();

        $pendingVetApprovals = (int) MovementPermit::query()
            ->whereIn('farmer_id', $farmerIds)
            ->where('veterinary_status', MovementPermit::VET_PENDING)
            ->whereIn('permit_status', [MovementPermit::STATUS_PENDING_APPROVAL, MovementPermit::STATUS_APPROVED])
            ->count();

        $feedExpiringSoon = (int) FeedInventory::query()
            ->whereIn('feed_type_id', DB::table('feed_types')->whereIn('business_id', $farmerIds)->pluck('id'))
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$today, $today->copy()->addDays(14)])
            ->count();

        $monthlyRevenue = (float) Sale::query()
            ->whereIn('farm_id', $farmIds)
            ->where('sale_status', Sale::STATUS_COMPLETED)
            ->whereBetween('sale_date', [$monthStart, $today])
            ->sum('total_amount');

        $animalsSoldThisMonth = (int) DB::table('sale_animals')
            ->join('sales', 'sales.id', '=', 'sale_animals.sale_id')
            ->whereIn('sales.farm_id', $farmIds->all())
            ->where('sales.sale_status', Sale::STATUS_COMPLETED)
            ->whereBetween('sales.sale_date', [$monthStart, $today])
            ->whereNotNull('sale_animals.animal_id')
            ->count();

        $outstandingBalance = (float) Sale::query()
            ->whereIn('farm_id', $farmIds)
            ->whereIn('payment_status', [Sale::PAYMENT_PENDING, Sale::PAYMENT_PARTIAL, Sale::PAYMENT_OVERDUE])
            ->sum('total_amount');

        $averageSalePrice = (float) DB::table('sale_animals')
            ->join('sales', 'sales.id', '=', 'sale_animals.sale_id')
            ->whereIn('sales.farm_id', $farmIds->all())
            ->where('sales.sale_status', Sale::STATUS_COMPLETED)
            ->avg('sale_animals.sale_price');

        $passportCount = (int) AnimalCertificate::query()
            ->whereIn('animal_id', $animalIds)
            ->where('certificate_type', AnimalCertificate::TYPE_TRACEABILITY)
            ->where('certificate_status', AnimalCertificate::STATUS_ACTIVE)
            ->distinct('animal_id')
            ->count('animal_id');

        $verifiedPermits = (int) MovementLog::query()
            ->where('action_type', MovementLog::ACTION_VERIFIED)
            ->whereHas('movementPermit', fn ($query) => $query->whereIn('farmer_id', $farmerIds))
            ->count();

        $weightStats = Animal::query()
            ->whereIn('id', $animalIds)
            ->where('lifecycle_status', Animal::LIFECYCLE_ACTIVE)
            ->selectRaw('AVG(weight) as avg_weight, COUNT(*) as total')
            ->first();

        $marketReady = (int) Animal::query()
            ->whereIn('id', $animalIds)
            ->where('lifecycle_status', Animal::LIFECYCLE_ACTIVE)
            ->where('production_status', Animal::PRODUCTION_READY_FOR_SALE)
            ->count();

        $belowGrowthTarget = (int) Animal::query()
            ->whereIn('id', $animalIds)
            ->where('lifecycle_status', Animal::LIFECYCLE_ACTIVE)
            ->where(function ($query): void {
                $query->whereNull('weight')->orWhere('weight', '<', 280);
            })
            ->count();

        $missingWeightUpdates = (int) Animal::query()
            ->whereIn('id', $animalIds)
            ->where('lifecycle_status', Animal::LIFECYCLE_ACTIVE)
            ->whereNull('weight')
            ->count();

        $fastestGroup = Livestock::query()
            ->whereIn('farm_id', $farmIds)
            ->withAvg(['animals as avg_animal_weight' => fn ($query) => $query->whereNotNull('weight')], 'weight')
            ->orderByDesc('avg_animal_weight')
            ->value('livestock_name');

        $complianceRate = $animalIds->count() > 0
            ? round(($passportCount / $animalIds->count()) * 100, 1)
            : 0.0;

        $operational = [
            'total_farms' => (int) $farmIds->count(),
            'livestock_groups' => (int) Livestock::query()->whereIn('farm_id', $farmIds)->count(),
            'active_animals' => $activeAnimals,
            'animals_in_transit' => (int) $movement['animals_in_transit'],
            'animals_sold' => $soldAnimals,
            'active_certificates' => (int) $certificates['active'],
            'active_movement_permits' => (int) $movement['active_permits'],
            'total_buyers' => (int) Buyer::query()->whereIn('business_id', $farmerIds)->count(),
        ];

        $healthCompliance = array_merge($health, [
            'healthy_animals' => $healthyAnimals,
            'quarantined_animals' => $quarantinedAnimals,
            'vaccinations_due' => $vaccinationsDueToday + (int) $health['upcoming_vaccinations'],
            'expired_certificates' => $expiredCertificates,
            'pending_vet_approvals' => $pendingVetApprovals,
        ]);

        $feedingInventory = array_merge($feeding, [
            'feed_expiring_soon' => $feedExpiringSoon,
            'feed_cost_month' => $this->monthlyFeedCost($farmerIds, $monthStart, $today),
        ]);

        $growth = [
            'average_weight' => $weightStats?->avg_weight !== null ? round((float) $weightStats->avg_weight, 1) : null,
            'average_daily_gain' => $this->estimateAverageDailyGain($animalIds),
            'market_ready_animals' => $marketReady,
            'below_growth_target' => $belowGrowthTarget,
            'fastest_growing_group' => $fastestGroup ?: __('Not enough data'),
        ];

        $financial = array_merge($sales, [
            'monthly_revenue' => $monthlyRevenue,
            'animals_sold_month' => $animalsSoldThisMonth,
            'outstanding_balance' => $outstandingBalance,
            'average_sale_price' => round($averageSalePrice, 0),
        ]);

        $traceability = [
            'verified_animals' => (int) $certificates['verifications'],
            'qr_verification_scans' => (int) $certificates['verifications'],
            'public_verification_requests' => (int) $certificates['verifications'],
            'active_passports' => $passportCount,
            'compliance_rate' => $complianceRate,
            'verified_certificates' => (int) $certificates['active'],
            'verified_movement_permits' => $verifiedPermits,
        ];

        $alerts = $this->buildAlerts(
            $animalIds,
            $farmIds,
            $farmerIds,
            $today,
            $health,
            $feeding,
            $movement,
            $expiredCertificates,
            $certificatesExpiringSoon,
            $vaccinationsDueToday,
            $missingWeightUpdates,
        );

        $charts = $this->buildCharts($animalIds, $farmIds, $farmerIds);
        $activities = $this->buildActivities($animalIds, $farmIds, $farmerIds);

        return compact(
            'operational',
            'healthCompliance',
            'feedingInventory',
            'growth',
            'financial',
            'traceability',
            'alerts',
            'charts',
            'activities',
        );
    }

    /**
     * @param  Collection<int, int>  $farmerIds
     */
    private function monthlyFeedCost(Collection $farmerIds, Carbon $start, Carbon $end): float
    {
        $feedTypeIds = DB::table('feed_types')->whereIn('business_id', $farmerIds)->pluck('id');

        return (float) DB::table('feeding_records')
            ->join('feed_inventories', 'feeding_records.feed_inventory_id', '=', 'feed_inventories.id')
            ->whereIn('feeding_records.feed_type_id', $feedTypeIds)
            ->whereBetween('feeding_records.feeding_date', [$start, $end])
            ->whereNull('feeding_records.deleted_at')
            ->selectRaw('SUM(feeding_records.quantity * COALESCE(feed_inventories.unit_cost, 0)) as total')
            ->value('total');
    }

    /**
     * @param  Collection<int, int>  $animalIds
     */
    private function estimateAverageDailyGain(Collection $animalIds): ?float
    {
        $rows = Animal::query()
            ->whereIn('id', $animalIds)
            ->whereNotNull('weight')
            ->whereNotNull('birth_date')
            ->get(['weight', 'birth_date']);

        if ($rows->isEmpty()) {
            return null;
        }

        $total = 0.0;
        $count = 0;
        foreach ($rows as $animal) {
            $days = max(1, $animal->birth_date?->diffInDays(now()) ?? 0);
            $total += ((float) $animal->weight) / $days;
            $count++;
        }

        return $count > 0 ? round($total / $count, 2) : null;
    }

    /**
     * @param  Collection<int, int>  $animalIds
     * @param  Collection<int, int>  $farmIds
     * @param  Collection<int, int>  $farmerIds
     * @param  array<string, mixed>  $health
     * @param  array<string, mixed>  $feeding
     * @param  array<string, mixed>  $movement
     * @return list<array<string, mixed>>
     */
    private function buildAlerts(
        Collection $animalIds,
        Collection $farmIds,
        Collection $farmerIds,
        Carbon $today,
        array $health,
        array $feeding,
        array $movement,
        int $expiredCertificates,
        int $certificatesExpiringSoon,
        int $vaccinationsDueToday,
        int $missingWeightUpdates,
    ): array {
        $alerts = [];

        if ($vaccinationsDueToday > 0) {
            $alerts[] = [
                'priority' => 'high',
                'title' => __('Vaccinations due today'),
                'detail' => __(':count animals need vaccination today.', ['count' => $vaccinationsDueToday]),
                'href' => route('farmer.health.vaccinations.index'),
                'action' => __('Review vaccinations'),
            ];
        }

        if ((int) $health['overdue_vaccinations'] > 0) {
            $alerts[] = [
                'priority' => 'high',
                'title' => __('Overdue vaccinations'),
                'detail' => __(':count vaccinations are overdue.', ['count' => (int) $health['overdue_vaccinations']]),
                'href' => route('farmer.health.vaccinations.index'),
                'action' => __('Schedule follow-up'),
            ];
        }

        if ((int) $movement['expired_permits'] > 0) {
            $alerts[] = [
                'priority' => 'medium',
                'title' => __('Expired movement permits'),
                'detail' => __(':count permits need renewal or closure.', ['count' => (int) $movement['expired_permits']]),
                'href' => route('farmer.movement.permits.index'),
                'action' => __('Open permits'),
            ];
        }

        if ((int) $health['sick_animals'] > 0) {
            $alerts[] = [
                'priority' => 'high',
                'title' => __('Sick animals'),
                'detail' => __(':count animals are flagged sick or injured.', ['count' => (int) $health['sick_animals']]),
                'href' => route('farmer.health.hub'),
                'action' => __('Open health hub'),
            ];
        }

        if ((int) $feeding['low_stock_alerts'] > 0) {
            $alerts[] = [
                'priority' => 'medium',
                'title' => __('Low feed stock'),
                'detail' => __(':count inventory batches are below reorder level.', ['count' => (int) $feeding['low_stock_alerts']]),
                'href' => route('farmer.feeding.inventory.index'),
                'action' => __('Check inventory'),
            ];
        }

        if ($missingWeightUpdates > 0) {
            $alerts[] = [
                'priority' => 'low',
                'title' => __('Missing weight updates'),
                'detail' => __(':count active animals have no recorded weight.', ['count' => $missingWeightUpdates]),
                'href' => route('farmer.animals.index'),
                'action' => __('Update animals'),
            ];
        }

        $pendingSales = (int) Sale::query()
            ->whereIn('farm_id', $farmIds)
            ->whereIn('sale_status', [Sale::STATUS_PENDING, Sale::STATUS_CONFIRMED])
            ->count();
        if ($pendingSales > 0) {
            $alerts[] = [
                'priority' => 'medium',
                'title' => __('Pending sales approval'),
                'detail' => __(':count sales are awaiting completion or payment.', ['count' => $pendingSales]),
                'href' => route('farmer.sales.hub'),
                'action' => __('Review sales'),
            ];
        }

        if ($certificatesExpiringSoon > 0) {
            $alerts[] = [
                'priority' => 'medium',
                'title' => __('Certificates expiring soon'),
                'detail' => __(':count certificates expire within 30 days.', ['count' => $certificatesExpiringSoon]),
                'href' => route('farmer.certificates.hub'),
                'action' => __('Renew certificates'),
            ];
        }

        if ($expiredCertificates > 0) {
            $alerts[] = [
                'priority' => 'high',
                'title' => __('Expired certificates'),
                'detail' => __(':count certificates are no longer valid.', ['count' => $expiredCertificates]),
                'href' => route('farmer.certificates.animal-certificates.index'),
                'action' => __('Review certificates'),
            ];
        }

        $pendingVet = (int) MovementPermit::query()
            ->whereIn('farmer_id', $farmerIds)
            ->where('veterinary_status', MovementPermit::VET_PENDING)
            ->count();
        if ($pendingVet > 0) {
            $alerts[] = [
                'priority' => 'medium',
                'title' => __('Pending vet inspections'),
                'detail' => __(':count movement permits await veterinary clearance.', ['count' => $pendingVet]),
                'href' => route('farmer.movement.hub'),
                'action' => __('Open movement hub'),
            ];
        }

        return $alerts;
    }

    /**
     * @param  Collection<int, int>  $animalIds
     * @param  Collection<int, int>  $farmIds
     * @param  Collection<int, int>  $farmerIds
     * @return array<string, array<string, mixed>>
     */
    private function buildCharts(Collection $animalIds, Collection $farmIds, Collection $farmerIds): array
    {
        $months = collect(range(5, 0))->map(fn (int $offset) => Carbon::today()->startOfMonth()->subMonths($offset));
        $labels = $months->map(fn (Carbon $month) => $month->format('M Y'))->all();

        $lifecycle = $months->map(function (Carbon $month) use ($animalIds): array {
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            return [
                'active' => Animal::query()->whereIn('id', $animalIds)->where('lifecycle_status', Animal::LIFECYCLE_ACTIVE)->whereDate('created_at', '<=', $end)->count(),
                'sold' => Animal::query()->whereIn('id', $animalIds)->where('lifecycle_status', Animal::LIFECYCLE_SOLD)->whereBetween('updated_at', [$start, $end])->count(),
                'dead' => MortalityRecord::query()->whereIn('animal_id', $animalIds)->whereBetween('death_date', [$start, $end])->count(),
                'quarantined' => Animal::query()->whereIn('id', $animalIds)->where('health_status', Animal::HEALTH_QUARANTINED)->whereBetween('updated_at', [$start, $end])->count(),
            ];
        });

        $healthCharts = $this->healthDashboard->charts($animalIds);
        $feedingCharts = $this->feedAnalytics->charts($farmerIds);
        $salesCharts = $this->saleAnalytics->charts($farmIds);
        $certificateCharts = $this->certificateAnalytics->charts($animalIds);
        $movementCharts = $this->movementAnalytics->charts($farmerIds);

        return [
            'animal_lifecycle_trend' => [
                'labels' => $labels,
                'stacked' => true,
                'datasets' => [
                    ['label' => __('Active'), 'data' => $lifecycle->pluck('active')->all(), 'backgroundColor' => 'rgba(16, 185, 129, 0.65)'],
                    ['label' => __('Sold'), 'data' => $lifecycle->pluck('sold')->all(), 'backgroundColor' => 'rgba(37, 99, 235, 0.55)'],
                    ['label' => __('Dead'), 'data' => $lifecycle->pluck('dead')->all(), 'backgroundColor' => 'rgba(15, 23, 42, 0.55)'],
                    ['label' => __('Quarantined'), 'data' => $lifecycle->pluck('quarantined')->all(), 'backgroundColor' => 'rgba(245, 158, 11, 0.65)'],
                ],
            ],
            'sales_revenue_trend' => $salesCharts['revenue_trend'],
            'health_vaccination_trend' => $healthCharts['vaccination_trend'],
            'health_mortality_trend' => $healthCharts['mortality_trend'],
            'feeding_usage_trend' => $feedingCharts['feed_usage_trend'],
            'feeding_cost_trend' => $feedingCharts['feed_cost_trend'],
            'traceability_verification_trend' => $certificateCharts['verification_trend'],
            'movement_approval_trend' => $movementCharts['approval_trend'],
        ];
    }

    /**
     * @param  Collection<int, int>  $animalIds
     * @param  Collection<int, int>  $farmIds
     * @param  Collection<int, int>  $farmerIds
     * @return list<array<string, mixed>>
     */
    private function buildActivities(Collection $animalIds, Collection $farmIds, Collection $farmerIds): array
    {
        $items = collect();

        Animal::query()
            ->whereIn('id', $animalIds)
            ->latest()
            ->limit(6)
            ->get(['id', 'animal_code', 'tag_number', 'created_at'])
            ->each(function (Animal $animal) use ($items): void {
                $items->push([
                    'at' => $animal->created_at,
                    'icon' => 'animal',
                    'title' => __('Animal registered'),
                    'detail' => $animal->displayIdentifier(),
                    'href' => route('farmer.animals.index'),
                ]);
            });

        AnimalCertificate::query()
            ->whereIn('animal_id', $animalIds)
            ->latest('issue_date')
            ->limit(5)
            ->get(['id', 'certificate_number', 'issue_date'])
            ->each(function (AnimalCertificate $certificate) use ($items): void {
                $items->push([
                    'at' => $certificate->issue_date?->startOfDay(),
                    'icon' => 'certificate',
                    'title' => __('Certificate issued'),
                    'detail' => $certificate->certificate_number,
                    'href' => route('farmer.certificates.animal-certificates.show', $certificate),
                ]);
            });

        MovementPermit::query()
            ->whereIn('farmer_id', $farmerIds)
            ->where('permit_status', MovementPermit::STATUS_APPROVED)
            ->latest('updated_at')
            ->limit(5)
            ->get(['id', 'permit_number', 'updated_at'])
            ->each(function (MovementPermit $permit) use ($items): void {
                $items->push([
                    'at' => $permit->updated_at,
                    'icon' => 'movement',
                    'title' => __('Movement permit approved'),
                    'detail' => $permit->permit_number,
                    'href' => route('farmer.movement.permits.show', $permit),
                ]);
            });

        Sale::query()
            ->whereIn('farm_id', $farmIds)
            ->where('sale_status', Sale::STATUS_COMPLETED)
            ->latest('sale_date')
            ->limit(5)
            ->get(['id', 'sale_number', 'sale_date'])
            ->each(function (Sale $sale) use ($items): void {
                $items->push([
                    'at' => $sale->sale_date?->startOfDay(),
                    'icon' => 'sale',
                    'title' => __('Sale completed'),
                    'detail' => $sale->sale_number,
                    'href' => route('farmer.sales.records.show', $sale),
                ]);
            });

        Vaccination::query()
            ->whereIn('animal_id', $animalIds)
            ->latest('vaccination_date')
            ->limit(5)
            ->get(['id', 'vaccination_code', 'vaccination_date'])
            ->each(function (Vaccination $record) use ($items): void {
                $items->push([
                    'at' => $record->vaccination_date?->startOfDay(),
                    'icon' => 'health',
                    'title' => __('Vaccination recorded'),
                    'detail' => $record->vaccination_code,
                    'href' => route('farmer.health.vaccinations.index'),
                ]);
            });

        AnimalCertificateLog::query()
            ->where('action_type', AnimalCertificateLog::ACTION_VERIFIED)
            ->whereHas('certificate', fn ($query) => $query->whereIn('animal_id', $animalIds))
            ->latest('action_date')
            ->limit(5)
            ->with('certificate')
            ->get()
            ->each(function (AnimalCertificateLog $log) use ($items): void {
                $items->push([
                    'at' => $log->action_date,
                    'icon' => 'verify',
                    'title' => __('Certificate verified'),
                    'detail' => $log->certificate?->certificate_number,
                    'href' => route('farmer.certificates.logs.index'),
                ]);
            });

        Buyer::query()
            ->whereIn('business_id', $farmerIds)
            ->latest()
            ->limit(4)
            ->get(['id', 'buyer_name', 'created_at'])
            ->each(function (Buyer $buyer) use ($items): void {
                $items->push([
                    'at' => $buyer->created_at,
                    'icon' => 'buyer',
                    'title' => __('Buyer added'),
                    'detail' => $buyer->buyer_name,
                    'href' => route('farmer.sales.buyers.index'),
                ]);
            });

        return $items
            ->filter(fn (array $item) => $item['at'] !== null)
            ->sortByDesc('at')
            ->take(16)
            ->values()
            ->all();
    }
}
