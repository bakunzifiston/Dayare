<?php

namespace App\Services\SuperAdmin;

use App\Models\AdministrativeDivision;
use App\Models\DeliveryConfirmation;
use App\Models\Demand;
use App\Models\Facility;
use App\Models\TransportTrip;
use App\Support\TenantEnvironmentScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class RicaSupplyChainDashboardService
{
    public function __construct(
        private readonly SuperAdminSlaughterDashboardService $slaughterDashboard,
        private readonly RicaSupplyChainDestinationResolver $destinationResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(Request $request): array
    {
        $filters = $this->slaughterDashboard->resolveHubFilters($request);
        $selectedDistrictId = $this->normalizeDistrictId($request->query('district_id'));
        $rows = $this->distributionRows($filters, $selectedDistrictId);
        $previousRows = $this->distributionRows($this->previousPeriodFilters($filters), $selectedDistrictId);

        $kpis = $this->kpis($rows, $previousRows);
        $chartSpecs = $this->chartSpecs($rows, $filters);
        $flowData = $this->meatFlowData($rows);
        $districtMap = $this->districtMapData($rows);

        return [
            'filters' => $filters,
            'districtOptions' => $this->districtOptions(),
            'selectedDistrictId' => $selectedDistrictId,
            'kpis' => $kpis,
            'chartSpecs' => $chartSpecs,
            'flowData' => $flowData,
            'districtMap' => $districtMap,
        ];
    }

    /**
     * @return list<array{
     *     trip_id: int,
     *     certificate_id: int|null,
     *     origin: string,
     *     origin_district_id: int|null,
     *     destination: string,
     *     destination_district: string|null,
     *     destination_district_id: int|null,
     *     kg: float,
     *     issued_at: ?Carbon,
     *     departure_date: ?Carbon,
     *     received_date: ?Carbon,
     *     is_confirmed: bool,
     *     is_compliant: bool
     * }>
     */
    private function distributionRows(array $filters, ?int $districtId): array
    {
        return $this->tripsQuery($filters, $districtId)
            ->get()
            ->map(function (TransportTrip $trip): array {
                $destination = $this->destinationResolver->resolveForTrip($trip);
                $certificate = $trip->certificate;
                $confirmation = $trip->deliveryConfirmation;

                return [
                    'trip_id' => (int) $trip->id,
                    'certificate_id' => $certificate?->id,
                    'origin' => $trip->originFacility?->facility_name ?? __('Unknown origin'),
                    'origin_district_id' => $trip->originFacility?->district_id ? (int) $trip->originFacility->district_id : null,
                    'destination' => $destination['label'],
                    'destination_district' => $destination['district'],
                    'destination_district_id' => $destination['district_id'],
                    'kg' => $this->destinationResolver->resolveTripQtyKg($trip),
                    'issued_at' => $certificate?->issued_at,
                    'departure_date' => $trip->departure_date,
                    'received_date' => $confirmation?->received_date,
                    'is_confirmed' => $confirmation?->confirmation_status === DeliveryConfirmation::STATUS_CONFIRMED,
                    'is_compliant' => $certificate?->isCompliant() ?? false,
                ];
            })
            ->filter(fn (array $row): bool => $row['kg'] > 0 || $row['certificate_id'] !== null)
            ->values()
            ->all();
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     */
    private function tripsQuery(array $filters, ?int $districtId): Builder
    {
        $query = TransportTrip::query()
            ->with([
                'certificate.batch',
                'certificate.warehouseStorages',
                'originFacility.districtDivision',
                'destinationFacility.districtDivision',
                'destinationFacility.sectorDivision',
                'destinationFacility.cell',
                'deliveryConfirmation.client.districtDivision',
                'deliveryConfirmation.client.sectorDivision',
                'deliveryConfirmation.client.cell',
                'deliveryConfirmation.receivingFacility.districtDivision',
            ])
            ->whereHas('originFacility', fn (Builder $facilityQuery) => TenantEnvironmentScope::applyToFacilities($facilityQuery));

        if ($filters['is_filtered'] && $filters['start'] !== null && $filters['end'] !== null) {
            $start = $filters['start']->copy()->startOfDay();
            $end = $filters['end']->copy()->endOfDay();

            $query->where(function (Builder $scoped) use ($start, $end): void {
                $scoped->whereBetween('departure_date', [$start, $end])
                    ->orWhereHas('deliveryConfirmation', fn (Builder $confirmationQuery) => $confirmationQuery
                        ->whereBetween('received_date', [$start, $end]))
                    ->orWhereHas('certificate', fn (Builder $certificateQuery) => $certificateQuery
                        ->whereBetween('issued_at', [$start, $end]));
            });
        }

        if ($districtId !== null) {
            $query->where(function (Builder $scoped) use ($districtId): void {
                $scoped->whereHas('originFacility', fn (Builder $facilityQuery) => $facilityQuery->where('district_id', $districtId))
                    ->orWhereHas('destinationFacility', fn (Builder $facilityQuery) => $facilityQuery->where('district_id', $districtId))
                    ->orWhereHas('deliveryConfirmation.client', fn (Builder $clientQuery) => $clientQuery->where('district_id', $districtId))
                    ->orWhereHas('deliveryConfirmation.receivingFacility', fn (Builder $facilityQuery) => $facilityQuery->where('district_id', $districtId));
            });
        }

        return $query;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<array<string, mixed>>  $previousRows
     * @return array<string, mixed>
     */
    private function kpis(array $rows, array $previousRows): array
    {
        $meatDelivered = round(collect($rows)->sum('kg'), 2);
        $previousMeat = round(collect($previousRows)->sum('kg'), 2);

        $certificateIds = collect($rows)->pluck('certificate_id')->filter()->unique();
        $previousCertificateIds = collect($previousRows)->pluck('certificate_id')->filter()->unique();

        $destinationsServed = collect($rows)->pluck('destination')->filter()->unique()->count();
        $previousDestinations = collect($previousRows)->pluck('destination')->filter()->unique()->count();

        $confirmed = collect($rows)->where('is_confirmed', true)->count();
        $complianceBase = collect($rows)->count();
        $complianceRate = $complianceBase > 0 ? round($confirmed / $complianceBase * 100, 1) : 0.0;

        $previousConfirmed = collect($previousRows)->where('is_confirmed', true)->count();
        $previousBase = collect($previousRows)->count();
        $previousCompliance = $previousBase > 0 ? round($previousConfirmed / $previousBase * 100, 1) : 0.0;

        return [
            'meat_delivered_kg' => [
                'value' => $meatDelivered,
                'trend' => $this->trendDelta($meatDelivered, $previousMeat),
            ],
            'certificates_issued' => [
                'value' => $certificateIds->count(),
                'trend' => $this->trendDelta($certificateIds->count(), $previousCertificateIds->count()),
            ],
            'destinations_served' => [
                'value' => $destinationsServed,
                'trend' => $this->trendDelta($destinationsServed, $previousDestinations),
            ],
            'compliance_rate' => [
                'value' => $complianceRate,
                'trend' => $this->trendDelta($complianceRate, $previousCompliance),
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function chartSpecs(array $rows, array $filters): array
    {
        $colors = config('bucha.chart.series', ['#A11D1E', '#7A1516', '#3C3C3B', '#718096', '#D69E2E', '#38A169']);
        $primary = $colors[0] ?? '#A11D1E';
        $burgundy = $colors[1] ?? '#7A1516';
        $charcoal = $colors[2] ?? '#3C3C3B';

        $destinationTotals = collect($rows)
            ->groupBy('destination')
            ->map(fn (Collection $group) => round($group->sum('kg'), 2))
            ->sortDesc()
            ->take(8);

        $destinationLabels = $destinationTotals->keys()->values()->all();
        $destinationData = $destinationTotals->values()->all();
        $destinationColors = collect($destinationLabels)->map(
            fn (string $_, int $index) => $colors[$index % count($colors)]
        )->all();

        $certificateTrend = $this->certificateUsageTrend($rows, $filters);
        $certificateByDestination = $this->certificatesByDestination($rows);
        $demandVsSupply = $this->demandVsSupply($rows, $filters);

        return [
            [
                'id' => 'rica-sc-destination-donut',
                'title' => __('Delivery by destination (kg)'),
                'type' => 'donut',
                'height' => 280,
                'labels' => $destinationLabels,
                'data' => $destinationData,
                'colors' => $destinationColors,
                'centerLabel' => __('Total (kg)'),
                'emptyMessage' => __('No deliveries for this period.'),
            ],
            [
                'id' => 'rica-sc-certificate-usage',
                'title' => __('Certificate usage over time'),
                'type' => 'bar',
                'height' => 260,
                'labels' => $certificateTrend['labels'],
                'datasets' => [[
                    'label' => __('Certificates issued'),
                    'data' => $certificateTrend['data'],
                    'backgroundColor' => $primary,
                ]],
                'emptyMessage' => __('No certificates issued for this period.'),
            ],
            [
                'id' => 'rica-sc-certificates-destination',
                'title' => __('Certificates by destination'),
                'type' => 'bar',
                'indexAxis' => 'y',
                'height' => 300,
                'labels' => $certificateByDestination['labels'],
                'datasets' => [[
                    'label' => __('Certificates'),
                    'data' => $certificateByDestination['data'],
                    'backgroundColor' => collect($certificateByDestination['labels'])->map(
                        fn (string $_, int $index) => $this->shadeColor($primary, $index)
                    )->all(),
                ]],
                'emptyMessage' => __('No certificate destinations for this period.'),
            ],
            array_merge($demandVsSupply, [
                'id' => 'rica-sc-demand-supply',
                'title' => __('Destination demand vs supply (kg)'),
                'type' => 'combo',
                'height' => 280,
                'fullWidth' => true,
                'emptyMessage' => __('No demand or delivery data for this period.'),
            ]),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{nodes: array<int, array<string, mixed>>, links: array<int, array<string, mixed>>}
     */
    private function meatFlowData(array $rows): array
    {
        $origins = collect($rows)
            ->groupBy('origin')
            ->map(fn (Collection $group) => round($group->sum('kg'), 2))
            ->sortDesc()
            ->take(8);

        $destinations = collect($rows)
            ->groupBy('destination')
            ->map(fn (Collection $group) => round($group->sum('kg'), 2))
            ->sortDesc()
            ->take(8);

        $links = collect($rows)
            ->groupBy(fn (array $row): string => $row['origin'].'|'.$row['destination'])
            ->map(fn (Collection $group, string $key): array => [
                'source' => explode('|', $key, 2)[0],
                'target' => explode('|', $key, 2)[1],
                'value' => round($group->sum('kg'), 2),
            ])
            ->sortByDesc('value')
            ->take(24)
            ->values()
            ->all();

        return [
            'nodes' => [
                'origins' => $origins->map(fn (float $value, string $label) => ['label' => $label, 'value' => $value])->values()->all(),
                'destinations' => $destinations->map(fn (float $value, string $label) => ['label' => $label, 'value' => $value])->values()->all(),
            ],
            'links' => $links,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{id: int, name: string, kg: float, intensity: float}>
     */
    private function districtMapData(array $rows): array
    {
        $districts = AdministrativeDivision::query()
            ->where('type', AdministrativeDivision::TYPE_DISTRICT)
            ->orderBy('name')
            ->get(['id', 'name']);

        $kgByDistrictId = collect($rows)
            ->filter(fn (array $row): bool => $row['destination_district_id'] !== null)
            ->groupBy('destination_district_id')
            ->map(fn (Collection $group) => round($group->sum('kg'), 2));

        $kgByDistrictName = collect($rows)
            ->filter(fn (array $row): bool => $row['destination_district'] !== null && $row['destination_district_id'] === null)
            ->groupBy('destination_district')
            ->map(fn (Collection $group) => round($group->sum('kg'), 2));

        $maxKg = max(1.0, (float) $kgByDistrictId->max() ?: $kgByDistrictName->max() ?: 1.0);

        return $districts->map(function (AdministrativeDivision $district) use ($kgByDistrictId, $kgByDistrictName, $maxKg): array {
            $kg = (float) ($kgByDistrictId[$district->id] ?? $kgByDistrictName[$district->name] ?? 0);

            return [
                'id' => (int) $district->id,
                'name' => $district->name,
                'kg' => $kg,
                'intensity' => round($kg / $maxKg, 3),
            ];
        })->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{labels: list<string>, data: list<int>}
     */
    private function certificateUsageTrend(array $rows, array $filters): array
    {
        $issuedDates = collect($rows)
            ->filter(fn (array $row) => $row['issued_at'] !== null)
            ->groupBy(fn (array $row): string => $row['issued_at']->format('Y-m-d'))
            ->map(fn (Collection $group) => $group->pluck('certificate_id')->filter()->unique()->count());

        if ($issuedDates->isEmpty()) {
            return ['labels' => [], 'data' => []];
        }

        $start = $filters['start'] ?? $issuedDates->keys()->sort()->first();
        $end = $filters['end'] ?? $issuedDates->keys()->sort()->last();
        $start = $start instanceof Carbon ? $start->copy()->startOfDay() : Carbon::parse($start)->startOfDay();
        $end = $end instanceof Carbon ? $end->copy()->endOfDay() : Carbon::parse($end)->endOfDay();
        $days = max(1, $start->diffInDays($end) + 1);
        $bucketCount = min(5, max(1, (int) ceil($days / 7)));
        $bucketSize = (int) ceil($days / $bucketCount);

        $labels = [];
        $data = [];
        $cursor = $start->copy();

        for ($bucket = 0; $bucket < $bucketCount; $bucket++) {
            $bucketStart = $cursor->copy();
            $bucketEnd = $cursor->copy()->addDays($bucketSize - 1)->min($end);
            $labels[] = $bucketStart->format('M j').'–'.$bucketEnd->format('M j');
            $count = collect($rows)
                ->filter(function (array $row) use ($bucketStart, $bucketEnd): bool {
                    if ($row['issued_at'] === null) {
                        return false;
                    }

                    return $row['issued_at']->between($bucketStart->startOfDay(), $bucketEnd->endOfDay());
                })
                ->pluck('certificate_id')
                ->filter()
                ->unique()
                ->count();
            $data[] = $count;
            $cursor = $bucketEnd->copy()->addDay();
            if ($cursor->gt($end)) {
                break;
            }
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{labels: list<string>, data: list<int>}
     */
    private function certificatesByDestination(array $rows): array
    {
        $counts = collect($rows)
            ->filter(fn (array $row) => $row['certificate_id'] !== null)
            ->groupBy('destination')
            ->map(fn (Collection $group) => $group->pluck('certificate_id')->unique()->count())
            ->sortDesc()
            ->take(8);

        return [
            'labels' => $counts->keys()->values()->all(),
            'data' => $counts->values()->all(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{labels: list<string>, datasets: list<array<string, mixed>>}
     */
    private function demandVsSupply(array $rows, array $filters): array
    {
        $delivered = collect($rows)
            ->groupBy('destination')
            ->map(fn (Collection $group) => round($group->sum('kg'), 2))
            ->sortDesc()
            ->take(8);

        $labels = $delivered->keys()->values()->all();
        if ($labels === []) {
            return ['labels' => [], 'datasets' => []];
        }

        $demandQuery = Demand::query()
            ->whereIn('status', [
                Demand::STATUS_CONFIRMED,
                Demand::STATUS_IN_PROGRESS,
                Demand::STATUS_FULFILLED,
            ])
            ->whereHas('business', fn (Builder $businessQuery) => TenantEnvironmentScope::applyToBusinesses($businessQuery))
            ->with(['destinationFacility', 'client']);

        if ($filters['is_filtered'] && $filters['start'] !== null && $filters['end'] !== null) {
            $demandQuery->whereBetween('requested_delivery_date', [
                $filters['start']->copy()->startOfDay(),
                $filters['end']->copy()->endOfDay(),
            ]);
        }

        $demandByDestination = $demandQuery->get()
            ->groupBy(fn (Demand $demand): string => $demand->destination_display)
            ->map(fn (Collection $group) => round($group->sum(fn (Demand $demand) => (float) $demand->quantity), 2));

        $primary = config('bucha.chart.series.0', '#A11D1E');
        $charcoal = config('bucha.chart.series.2', '#3C3C3B');

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'type' => 'bar',
                    'label' => __('Delivered (kg)'),
                    'data' => collect($labels)->map(fn (string $label) => $delivered[$label] ?? 0)->all(),
                    'backgroundColor' => $primary,
                ],
                [
                    'type' => 'line',
                    'label' => __('Demand (kg)'),
                    'data' => collect($labels)->map(fn (string $label) => $demandByDestination[$label] ?? 0)->all(),
                    'borderColor' => $charcoal,
                    'backgroundColor' => $charcoal,
                    'pointBackgroundColor' => $charcoal,
                ],
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function districtOptions(): array
    {
        return AdministrativeDivision::query()
            ->where('type', AdministrativeDivision::TYPE_DISTRICT)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon,
     *     period: string
     * }  $filters
     * @return array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon,
     *     period: string
     * }
     */
    private function previousPeriodFilters(array $filters): array
    {
        if (! $filters['is_filtered'] || $filters['start'] === null || $filters['end'] === null) {
            $end = now()->subMonth()->endOfMonth();
            $start = now()->subMonth()->startOfMonth();

            return [
                'is_filtered' => true,
                'start' => $start,
                'end' => $end,
                'period' => 'month',
            ];
        }

        $days = max(1, $filters['start']->diffInDays($filters['end']) + 1);
        $previousEnd = $filters['start']->copy()->subDay()->endOfDay();
        $previousStart = $previousEnd->copy()->subDays($days - 1)->startOfDay();

        return [
            'is_filtered' => true,
            'start' => $previousStart,
            'end' => $previousEnd,
            'period' => $filters['period'],
        ];
    }

    /**
     * @return array{direction: string, percent: float, label: string}
     */
    private function trendDelta(float|int $current, float|int $previous): array
    {
        if ($previous <= 0) {
            return [
                'direction' => $current > 0 ? 'up' : 'neutral',
                'percent' => 0.0,
                'label' => __('vs last month'),
            ];
        }

        $delta = (($current - $previous) / $previous) * 100;

        return [
            'direction' => $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'neutral'),
            'percent' => round(abs($delta), 1),
            'label' => __('vs last period'),
        ];
    }

    private function normalizeDistrictId(mixed $districtId): ?int
    {
        if ($districtId === null || $districtId === '' || $districtId === 'all') {
            return null;
        }

        return is_numeric($districtId) ? (int) $districtId : null;
    }

    private function shadeColor(string $hex, int $index): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return '#A11D1E';
        }

        $factor = max(0.45, 1 - ($index * 0.08));
        $r = (int) round(hexdec(substr($hex, 0, 2)) * $factor);
        $g = (int) round(hexdec(substr($hex, 2, 2)) * $factor);
        $b = (int) round(hexdec(substr($hex, 4, 2)) * $factor);

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
