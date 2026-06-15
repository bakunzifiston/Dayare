<?php

namespace App\Services\SuperAdmin;

use App\Models\AnimalIntake;
use App\Models\Business;
use App\Models\Certificate;
use App\Models\DeliveryConfirmation;
use App\Models\Demand;
use App\Models\Facility;
use App\Models\SlaughterExecution;
use App\Models\WarehouseStorage;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SuperAdminVibeAnalyticsService
{
    /**
     * @return array{
     *   kpis: array<string, int|float>,
     *   turnover: array{before: float, after: float, growth_pct: float},
     *   trend: array{charts: array<string, mixed>},
     *   insights: list<string>,
     *   certificate_validity_rate: float,
     *   operational_readiness: bool,
     *   operational_readiness_score: float,
     *   last_active_at: ?Carbon,
     *   last_active_label: string,
     *   is_processor: bool,
     *   is_farmer: bool
     * }
     */
    public function businessAnalytics(Business $business): array
    {
        $facilityIds = Facility::query()->where('business_id', $business->id)->pluck('id');
        $isProcessor = $business->type === Business::TYPE_PROCESSOR;
        $isFarmer = $business->type === Business::TYPE_FARMER;

        $animalIntakeRecords = (int) AnimalIntake::query()->whereIn('facility_id', $facilityIds)->count();
        $certificatesIssued = (int) Certificate::query()->whereIn('facility_id', $facilityIds)->count();
        $compliantCertificates = (int) Certificate::query()->whereIn('facility_id', $facilityIds)->compliant()->count();
        $confirmedDeliveries = (int) DeliveryConfirmation::query()
            ->whereIn('receiving_facility_id', $facilityIds)
            ->where('confirmation_status', DeliveryConfirmation::STATUS_CONFIRMED)
            ->count();
        $demandsTotal = (int) Demand::query()->where('business_id', $business->id)->count();
        $demandsFulfilled = (int) Demand::query()
            ->where('business_id', $business->id)
            ->where('status', Demand::STATUS_FULFILLED)
            ->count();

        $slaughterExecutions = 0;
        $coldRoomRecords = 0;
        if ($isProcessor) {
            $planIds = \App\Models\SlaughterPlan::query()->whereIn('facility_id', $facilityIds)->pluck('id');
            $slaughterExecutions = (int) SlaughterExecution::query()->whereIn('slaughter_plan_id', $planIds)->count();
            $coldRoomRecords = (int) WarehouseStorage::query()
                ->whereHas('warehouseFacility', fn ($q) => $q->where('business_id', $business->id))
                ->count();
        }

        $demandFulfillmentRate = $demandsTotal > 0
            ? round(($demandsFulfilled / $demandsTotal) * 100, 1)
            : 0.0;
        $certificateValidityRate = $certificatesIssued > 0
            ? round(($compliantCertificates / $certificatesIssued) * 100, 1)
            : 0.0;

        $operationalReadiness = $this->operationalReadiness($business, $facilityIds, $isProcessor, $isFarmer);
        $lastActive = $this->lastActiveAt($business, $facilityIds, $isProcessor);

        $beforeTurnover = (float) (Business::baselineRevenueMidpointRwf(
            $business->baseline_revenue !== null && $business->baseline_revenue !== ''
                ? (string) $business->baseline_revenue
                : null
        ) ?? 0);
        $afterTurnover = (float) AnimalIntake::query()
            ->whereIn('facility_id', $facilityIds)
            ->whereDate('intake_date', '>=', now()->subMonths(12)->startOfMonth())
            ->sum('total_price');
        $growthPct = $beforeTurnover > 0
            ? round((($afterTurnover - $beforeTurnover) / $beforeTurnover) * 100, 1)
            : ($afterTurnover > 0 ? 100.0 : 0.0);

        $trend = $this->businessTrendData($business, $facilityIds, $isProcessor);

        $insights = [
            __('Demand fulfillment is :value%.', ['value' => number_format($demandFulfillmentRate, 1)]),
            __('Certificate validity rate is :value%.', ['value' => number_format($certificateValidityRate, 1)]),
        ];

        if ($isProcessor) {
            $insights[] = $operationalReadiness
                ? __('Operational readiness: active slaughter and certification in the last 30 days.')
                : __('Operational readiness: no recent slaughter and certification activity in the last 30 days.');
        }

        if ($lastActive !== null) {
            $insights[] = __('Last activity on :date.', ['date' => $lastActive->format('M j, Y')]);
        }

        $kpis = [
            'facilities' => (int) $facilityIds->count(),
            'animal_intake_records' => $animalIntakeRecords,
            'confirmed_deliveries' => $confirmedDeliveries,
            'demand_fulfillment_rate' => $demandFulfillmentRate,
        ];

        if ($isProcessor) {
            $kpis['slaughter_executions'] = $slaughterExecutions;
            $kpis['certificates_issued'] = $certificatesIssued;
            $kpis['cold_room_records'] = $coldRoomRecords;
        }

        if ($isFarmer) {
            $kpis['confirmed_deliveries'] = $confirmedDeliveries;
            $kpis['demand_fulfillment_rate'] = $demandFulfillmentRate;
        }

        return [
            'kpis' => $kpis,
            'turnover' => [
                'before' => $beforeTurnover,
                'after' => $afterTurnover,
                'growth_pct' => $growthPct,
            ],
            'trend' => $trend,
            'insights' => $insights,
            'certificate_validity_rate' => $certificateValidityRate,
            'operational_readiness' => $operationalReadiness,
            'operational_readiness_score' => $operationalReadiness ? 100.0 : 0.0,
            'last_active_at' => $lastActive,
            'last_active_label' => $lastActive?->diffForHumans() ?? __('No recorded activity'),
            'is_processor' => $isProcessor,
            'is_farmer' => $isFarmer,
        ];
    }

    /**
     * @return array{
     *   registration: array{completed: int, total: int, percent: float, checks: array<string, bool>},
     *   operational: array{completed: int, total: int, percent: float, checks: array<string, bool>},
     *   overall: array{completed: int, total: int, percent: float}
     * }
     */
    public function businessDataCompleteness(Business $business): array
    {
        $registrationChecks = [
            'business_name' => filled($business->business_name),
            'registration_number' => filled($business->registration_number),
            'contact_phone' => filled($business->contact_phone),
            'email' => filled($business->email),
            'type' => filled($business->type),
            'owner_first_name' => filled($business->owner_first_name),
            'owner_last_name' => filled($business->owner_last_name),
            'ownership_type' => filled($business->ownership_type),
            'country_id' => ! empty($business->country_id),
            'district_id' => ! empty($business->district_id),
            'sector_id' => ! empty($business->sector_id),
            'cell_id' => ! empty($business->cell_id),
            'village_id' => ! empty($business->village_id),
        ];

        $operationalChecks = [
            'pathway_status' => filled($business->pathway_status),
            'vibe_unique_id' => filled($business->vibe_unique_id),
            'vibe_commencement_date' => ! empty($business->vibe_commencement_date),
            'baseline_revenue' => filled($business->baseline_revenue),
        ];

        $registration = $this->scoreGroup($registrationChecks);
        $operational = $this->scoreGroup($operationalChecks);
        $allChecks = array_merge($registrationChecks, $operationalChecks);

        return [
            'registration' => $registration,
            'operational' => $operational,
            'overall' => $this->scoreGroup($allChecks),
        ];
    }

    /**
     * @return list<string>
     */
    public function analyticsCsvHeaders(): array
    {
        return [
            'business_id',
            'business_name',
            'business_type',
            'certificate_validity_rate',
            'operational_readiness',
            'profile_completeness_registration_pct',
            'profile_completeness_operational_pct',
            'profile_completeness_overall_pct',
            'facilities',
            'intakes',
            'slaughter_executions',
            'certificates_issued',
            'cold_room_records',
            'confirmed_deliveries',
            'demand_fulfillment_rate',
            'last_active_at',
            'turnover_growth_pct',
        ];
    }

    /**
     * @return list<int|float|string|null>
     */
    public function analyticsCsvRow(Business $business): array
    {
        $analytics = $this->businessAnalytics($business);
        $completeness = $this->businessDataCompleteness($business);

        return [
            $business->id,
            $business->business_name,
            $business->type,
            $analytics['certificate_validity_rate'],
            $analytics['operational_readiness'] ? 'yes' : 'no',
            $completeness['registration']['percent'],
            $completeness['operational']['percent'],
            $completeness['overall']['percent'],
            $analytics['kpis']['facilities'] ?? 0,
            $analytics['kpis']['animal_intake_records'] ?? 0,
            $analytics['kpis']['slaughter_executions'] ?? '',
            $analytics['kpis']['certificates_issued'] ?? '',
            $analytics['kpis']['cold_room_records'] ?? '',
            $analytics['kpis']['confirmed_deliveries'] ?? 0,
            $analytics['kpis']['demand_fulfillment_rate'] ?? 0,
            $analytics['last_active_at']?->toDateString(),
            $analytics['turnover']['growth_pct'],
        ];
    }

    private function operationalReadiness(Business $business, Collection $facilityIds, bool $isProcessor, bool $isFarmer): bool
    {
        $since = now()->subDays(30)->startOfDay();

        if ($isProcessor) {
            $planIds = \App\Models\SlaughterPlan::query()->whereIn('facility_id', $facilityIds)->pluck('id');
            $recentSlaughter = SlaughterExecution::query()
                ->whereIn('slaughter_plan_id', $planIds)
                ->where('slaughter_time', '>=', $since)
                ->exists();
            $recentCertificate = Certificate::query()
                ->whereIn('facility_id', $facilityIds)
                ->where('issued_at', '>=', $since)
                ->exists();

            return $recentSlaughter && $recentCertificate;
        }

        if ($isFarmer) {
            $recentIntake = AnimalIntake::query()
                ->whereIn('facility_id', $facilityIds)
                ->whereDate('intake_date', '>=', $since)
                ->exists();
            $recentDelivery = DeliveryConfirmation::query()
                ->whereIn('receiving_facility_id', $facilityIds)
                ->where('confirmation_status', DeliveryConfirmation::STATUS_CONFIRMED)
                ->whereDate('received_date', '>=', $since)
                ->exists();

            return $recentIntake || $recentDelivery;
        }

        return false;
    }

    private function lastActiveAt(Business $business, Collection $facilityIds, bool $isProcessor): ?Carbon
    {
        $dates = collect();

        $latestIntake = AnimalIntake::query()
            ->whereIn('facility_id', $facilityIds)
            ->max('intake_date');
        if ($latestIntake) {
            $dates->push(Carbon::parse($latestIntake));
        }

        if ($isProcessor) {
            $planIds = \App\Models\SlaughterPlan::query()->whereIn('facility_id', $facilityIds)->pluck('id');
            $latestSlaughter = SlaughterExecution::query()
                ->whereIn('slaughter_plan_id', $planIds)
                ->max('slaughter_time');
            if ($latestSlaughter) {
                $dates->push(Carbon::parse($latestSlaughter));
            }
        }

        return $dates->sortDesc()->first();
    }

    /**
     * @param  array<string, bool>  $checks
     * @return array{completed: int, total: int, percent: float, checks: array<string, bool>}
     */
    private function scoreGroup(array $checks): array
    {
        $total = count($checks);
        $completed = count(array_filter($checks));
        $percent = $total > 0 ? round(($completed / $total) * 100, 1) : 0.0;

        return [
            'completed' => $completed,
            'total' => $total,
            'percent' => $percent,
            'checks' => $checks,
        ];
    }

    private function businessTrendData(Business $business, Collection $facilityIds, bool $isProcessor): array
    {
        $months = 6;
        $windowStart = now()->subMonths($months - 1)->startOfMonth();
        $monthKeys = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $monthKeys[] = now()->subMonths($i)->format('Y-m');
        }

        $intakes = AnimalIntake::query()
            ->whereIn('facility_id', $facilityIds)
            ->whereDate('intake_date', '>=', $windowStart)
            ->get();
        $intakeByMonth = $intakes
            ->groupBy(fn ($row) => Carbon::parse($row->intake_date)->format('Y-m'))
            ->map->count();
        $intakeValueByMonth = $intakes
            ->groupBy(fn ($row) => Carbon::parse($row->intake_date)->format('Y-m'))
            ->map(fn (Collection $rows) => (float) $rows->sum('total_price'));

        $certByMonth = collect();
        if ($isProcessor) {
            $certificates = Certificate::query()
                ->whereIn('facility_id', $facilityIds)
                ->whereDate('issued_at', '>=', $windowStart)
                ->get();
            $certByMonth = $certificates
                ->groupBy(fn ($row) => Carbon::parse($row->issued_at)->format('Y-m'))
                ->map->count();
        }

        $deliveries = DeliveryConfirmation::query()
            ->whereIn('receiving_facility_id', $facilityIds)
            ->where('confirmation_status', DeliveryConfirmation::STATUS_CONFIRMED)
            ->whereDate('received_date', '>=', $windowStart)
            ->get();
        $deliveryByMonth = $deliveries
            ->groupBy(fn ($row) => Carbon::parse($row->received_date)->format('Y-m'))
            ->map->count();

        $labels = array_map(
            fn (string $month) => Carbon::createFromFormat('Y-m', $month)->translatedFormat('M Y'),
            $monthKeys
        );

        $fill = static fn (Collection $values) => array_map(
            fn (string $month) => (float) ($values[$month] ?? 0),
            $monthKeys
        );

        $datasets = [
            ['label' => __('Intakes'), 'data' => $fill($intakeByMonth)],
        ];

        if ($isProcessor) {
            $datasets[] = ['label' => __('Certificates'), 'data' => $fill($certByMonth)];
        }

        $datasets[] = ['label' => __('Deliveries'), 'data' => $fill($deliveryByMonth)];

        return [
            'charts' => [
                'vibe_kpi_progress' => [
                    'type' => 'line',
                    'labels' => $labels,
                    'datasets' => $datasets,
                    'yTickPrecision' => 0,
                ],
                'vibe_turnover_progress' => [
                    'type' => 'bar',
                    'labels' => $labels,
                    'datasets' => [
                        ['label' => __('Turnover (estimated)'), 'data' => $fill($intakeValueByMonth)],
                    ],
                    'yTickPrecision' => 0,
                ],
            ],
        ];
    }
}
