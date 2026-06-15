<?php

namespace App\Services\SuperAdmin;

use App\Models\Batch;
use App\Models\Certificate;
use App\Models\Facility;
use App\Models\PostMortemInspectionItem;
use App\Models\SlaughterExecutionItem;
use App\Models\SlaughterPlan;
use App\Models\TemperatureLog;
use App\Models\WarehouseStorage;
use App\Support\TenantEnvironmentScope;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RicaReportService
{
    public const DATE_BASIS_SLAUGHTER = 'slaughter';

    public const DATE_BASIS_RECORD = 'record';

    /**
     * @return array{
     *   rows: LengthAwarePaginator,
     *   totals: array<string, int|float|null>,
     *   dateFrom: Carbon,
     *   dateTo: Carbon,
     *   dateBasis: string
     * }
     */
    public function buildReport(Request $request, int $perPage = 20): array
    {
        [$dateFrom, $dateTo, $dateBasis] = $this->resolveFilters($request);

        $facilitiesQuery = TenantEnvironmentScope::applyToFacilities(
            Facility::query()->where('facility_type', Facility::TYPE_SLAUGHTERHOUSE)
        )
            ->with('business')
            ->when($request->filled('business_id'), fn ($q) => $q->where('business_id', $request->integer('business_id')))
            ->when($request->filled('search'), fn ($q) => $q->where('facility_name', 'like', '%'.$request->string('search').'%'));

        $facilityIds = (clone $facilitiesQuery)->pluck('id');
        $metrics = $this->aggregateMetrics($facilityIds, $dateFrom, $dateTo, $dateBasis);

        $sort = (string) $request->query('sort', 'facility_name');
        $direction = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allRows = $facilitiesQuery
            ->orderBy('facility_name')
            ->get()
            ->map(function (Facility $facility) use ($metrics) {
                $id = (int) $facility->id;
                $row = $metrics[$id] ?? $this->emptyMetrics();

                return array_merge($row, [
                    'facility' => $facility,
                    'facility_id' => $id,
                ]);
            });

        $sorted = $this->sortRows($allRows, $sort, $direction);
        $page = max(1, (int) $request->query('page', 1));
        $items = $sorted->forPage($page, $perPage)->values();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $sorted->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return [
            'rows' => $paginator,
            'totals' => $this->sumTotals($sorted),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'dateBasis' => $dateBasis,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function allRowsForExport(Request $request): Collection
    {
        [$dateFrom, $dateTo, $dateBasis] = $this->resolveFilters($request);

        $facilities = TenantEnvironmentScope::applyToFacilities(
            Facility::query()->where('facility_type', Facility::TYPE_SLAUGHTERHOUSE)
        )
            ->with('business')
            ->when($request->filled('business_id'), fn ($q) => $q->where('business_id', $request->integer('business_id')))
            ->when($request->filled('search'), fn ($q) => $q->where('facility_name', 'like', '%'.$request->string('search').'%'))
            ->orderBy('facility_name')
            ->get();

        $metrics = $this->aggregateMetrics($facilities->pluck('id'), $dateFrom, $dateTo, $dateBasis);

        return $facilities->map(function (Facility $facility) use ($metrics) {
            $id = (int) $facility->id;

            return array_merge(
                ['facility' => $facility],
                $metrics[$id] ?? $this->emptyMetrics()
            );
        });
    }

    /**
     * @param  Collection<int, int|string>  $facilityIds
     * @return array<int, array<string, int|float|null>>
     */
    private function aggregateMetrics(Collection $facilityIds, Carbon $dateFrom, Carbon $dateTo, string $dateBasis): array
    {
        if ($facilityIds->isEmpty()) {
            return [];
        }

        $planMap = SlaughterPlan::query()
            ->whereIn('facility_id', $facilityIds)
            ->get(['id', 'facility_id'])
            ->groupBy('facility_id');

        $planToFacility = [];
        foreach ($planMap as $facilityId => $plans) {
            foreach ($plans as $plan) {
                $planToFacility[(int) $plan->id] = (int) $facilityId;
            }
        }

        $planIds = array_keys($planToFacility);
        $metrics = [];
        foreach ($facilityIds as $facilityId) {
            $metrics[(int) $facilityId] = $this->emptyMetrics();
        }

        if ($planIds === []) {
            return $metrics;
        }

        $this->applySlaughterMetrics($metrics, $planIds, $planToFacility, $dateFrom, $dateTo);
        $this->applyCondemnedMetrics($metrics, $planIds, $planToFacility, $dateFrom, $dateTo, $dateBasis);
        $this->applyCertificateMetrics($metrics, $planIds, $planToFacility, $dateFrom, $dateTo, $dateBasis);
        $this->applyAwaitingCertificateMetrics($metrics, $facilityIds);
        $this->applyColdRoomMetrics($metrics, $facilityIds, $dateFrom, $dateTo);
        $this->applyTemperatureMetrics($metrics, $facilityIds, $dateFrom, $dateTo);

        return $metrics;
    }

    /**
     * @param  array<int, array<string, int|float|null>>  $metrics
     * @param  list<int>  $planIds
     * @param  array<int, int>  $planToFacility
     */
    private function applySlaughterMetrics(array &$metrics, array $planIds, array $planToFacility, Carbon $dateFrom, Carbon $dateTo): void
    {
        $rows = SlaughterExecutionItem::query()
            ->join('slaughter_executions', 'slaughter_execution_items.slaughter_execution_id', '=', 'slaughter_executions.id')
            ->join('slaughter_plans', 'slaughter_executions.slaughter_plan_id', '=', 'slaughter_plans.id')
            ->whereIn('slaughter_plans.id', $planIds)
            ->whereBetween('slaughter_executions.slaughter_time', [$dateFrom, $dateTo])
            ->select([
                'slaughter_plans.facility_id',
                DB::raw('COUNT(*) as animals'),
                DB::raw('COALESCE(SUM(slaughter_execution_items.meat_quantity_kg), 0) as total_meat_kg'),
            ])
            ->groupBy('slaughter_plans.facility_id')
            ->get();

        foreach ($rows as $row) {
            $facilityId = (int) $row->facility_id;
            $metrics[$facilityId]['animals'] = (int) $row->animals;
            $metrics[$facilityId]['total_meat_kg'] = (float) $row->total_meat_kg;
        }
    }

    /**
     * @param  array<int, array<string, int|float|null>>  $metrics
     * @param  list<int>  $planIds
     * @param  array<int, int>  $planToFacility
     */
    private function applyCondemnedMetrics(
        array &$metrics,
        array $planIds,
        array $planToFacility,
        Carbon $dateFrom,
        Carbon $dateTo,
        string $dateBasis,
    ): void {
        $query = PostMortemInspectionItem::query()
            ->join('post_mortem_inspections', 'post_mortem_inspection_items.post_mortem_inspection_id', '=', 'post_mortem_inspections.id')
            ->join('batches', 'post_mortem_inspections.batch_id', '=', 'batches.id')
            ->join('slaughter_executions', 'batches.slaughter_execution_id', '=', 'slaughter_executions.id')
            ->join('slaughter_plans', 'slaughter_executions.slaughter_plan_id', '=', 'slaughter_plans.id')
            ->whereIn('slaughter_plans.id', $planIds)
            ->where('post_mortem_inspection_items.outcome', PostMortemInspectionItem::OUTCOME_CONDEMNED);

        if ($dateBasis === self::DATE_BASIS_RECORD) {
            $query->whereBetween('post_mortem_inspection_items.created_at', [$dateFrom, $dateTo]);
        } else {
            $query->whereBetween('slaughter_executions.slaughter_time', [$dateFrom, $dateTo]);
        }

        $rows = $query
            ->select(['slaughter_plans.facility_id', DB::raw('COUNT(*) as condemned')])
            ->groupBy('slaughter_plans.facility_id')
            ->get();

        foreach ($rows as $row) {
            $metrics[(int) $row->facility_id]['condemned'] = (int) $row->condemned;
        }
    }

    /**
     * @param  array<int, array<string, int|float|null>>  $metrics
     * @param  list<int>  $planIds
     * @param  array<int, int>  $planToFacility
     */
    private function applyCertificateMetrics(
        array &$metrics,
        array $planIds,
        array $planToFacility,
        Carbon $dateFrom,
        Carbon $dateTo,
        string $dateBasis,
    ): void {
        $query = Certificate::query()
            ->join('batches', 'certificates.batch_id', '=', 'batches.id')
            ->join('slaughter_executions', 'batches.slaughter_execution_id', '=', 'slaughter_executions.id')
            ->join('slaughter_plans', 'slaughter_executions.slaughter_plan_id', '=', 'slaughter_plans.id')
            ->whereIn('slaughter_plans.id', $planIds);

        if ($dateBasis === self::DATE_BASIS_RECORD) {
            $query->whereBetween('certificates.issued_at', [$dateFrom, $dateTo]);
        } else {
            $query->whereBetween('slaughter_executions.slaughter_time', [$dateFrom, $dateTo]);
        }

        $rows = $query
            ->select(['slaughter_plans.facility_id', DB::raw('COUNT(*) as certificates')])
            ->groupBy('slaughter_plans.facility_id')
            ->get();

        foreach ($rows as $row) {
            $metrics[(int) $row->facility_id]['certificates'] = (int) $row->certificates;
        }
    }

    /**
     * @param  array<int, array<string, int|float|null>>  $metrics
     * @param  Collection<int, int|string>  $facilityIds
     */
    private function applyAwaitingCertificateMetrics(array &$metrics, Collection $facilityIds): void
    {
        $rows = Batch::query()
            ->eligibleForCertificate()
            ->join('slaughter_executions', 'batches.slaughter_execution_id', '=', 'slaughter_executions.id')
            ->join('slaughter_plans', 'slaughter_executions.slaughter_plan_id', '=', 'slaughter_plans.id')
            ->whereIn('slaughter_plans.facility_id', $facilityIds)
            ->select(['slaughter_plans.facility_id', DB::raw('COUNT(DISTINCT batches.id) as awaiting_certificate')])
            ->groupBy('slaughter_plans.facility_id')
            ->get();

        foreach ($rows as $row) {
            $metrics[(int) $row->facility_id]['awaiting_certificate'] = (int) $row->awaiting_certificate;
        }
    }

    /**
     * @param  array<int, array<string, int|float|null>>  $metrics
     * @param  Collection<int, int|string>  $facilityIds
     */
    private function applyColdRoomMetrics(array &$metrics, Collection $facilityIds, Carbon $dateFrom, Carbon $dateTo): void
    {
        $rows = WarehouseStorage::query()
            ->join('batches', 'warehouse_storages.batch_id', '=', 'batches.id')
            ->join('slaughter_executions', 'batches.slaughter_execution_id', '=', 'slaughter_executions.id')
            ->join('slaughter_plans', 'slaughter_executions.slaughter_plan_id', '=', 'slaughter_plans.id')
            ->whereIn('slaughter_plans.facility_id', $facilityIds)
            ->where('warehouse_storages.status', WarehouseStorage::STATUS_RELEASED)
            ->whereNotNull('warehouse_storages.entry_date')
            ->whereNotNull('warehouse_storages.released_date')
            ->whereBetween('warehouse_storages.released_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->get(['slaughter_plans.facility_id', 'warehouse_storages.entry_date', 'warehouse_storages.released_date']);

        $grouped = $rows->groupBy(fn ($row) => (int) $row->facility_id);

        foreach ($grouped as $facilityId => $storages) {
            $days = $storages->map(function ($row) {
                $entry = Carbon::parse($row->entry_date);
                $released = Carbon::parse($row->released_date);

                return max(0, $entry->diffInDays($released));
            });

            if ($days->isNotEmpty()) {
                $metrics[(int) $facilityId]['avg_cold_room_days'] = round((float) $days->avg(), 1);
            }
        }
    }

    /**
     * @param  array<int, array<string, int|float|null>>  $metrics
     * @param  Collection<int, int|string>  $facilityIds
     */
    private function applyTemperatureMetrics(array &$metrics, Collection $facilityIds, Carbon $dateFrom, Carbon $dateTo): void
    {
        $rows = TemperatureLog::query()
            ->join('warehouse_storages', 'temperature_logs.warehouse_storage_id', '=', 'warehouse_storages.id')
            ->join('batches', 'warehouse_storages.batch_id', '=', 'batches.id')
            ->join('slaughter_executions', 'batches.slaughter_execution_id', '=', 'slaughter_executions.id')
            ->join('slaughter_plans', 'slaughter_executions.slaughter_plan_id', '=', 'slaughter_plans.id')
            ->whereIn('slaughter_plans.facility_id', $facilityIds)
            ->whereIn('temperature_logs.status', [TemperatureLog::STATUS_WARNING, TemperatureLog::STATUS_CRITICAL])
            ->whereBetween('temperature_logs.recorded_at', [$dateFrom, $dateTo])
            ->select(['slaughter_plans.facility_id', DB::raw('COUNT(*) as temperature_violations')])
            ->groupBy('slaughter_plans.facility_id')
            ->get();

        foreach ($rows as $row) {
            $metrics[(int) $row->facility_id]['temperature_violations'] = (int) $row->temperature_violations;
        }
    }

    /**
     * @return array{animals: int, total_meat_kg: float, condemned: int, certificates: int, awaiting_certificate: int, avg_cold_room_days: float|null, temperature_violations: int}
     */
    private function emptyMetrics(): array
    {
        return [
            'animals' => 0,
            'total_meat_kg' => 0.0,
            'condemned' => 0,
            'certificates' => 0,
            'awaiting_certificate' => 0,
            'avg_cold_room_days' => null,
            'temperature_violations' => 0,
        ];
    }

    /**
     * @return array{dateFrom: Carbon, dateTo: Carbon, dateBasis: string}
     */
    private function resolveFilters(Request $request): array
    {
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->string('date_from'))->startOfDay()
            : now()->startOfMonth();
        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->string('date_to'))->endOfDay()
            : now()->endOfMonth();
        $dateBasis = $request->string('date_basis', self::DATE_BASIS_SLAUGHTER);
        if (! in_array($dateBasis, [self::DATE_BASIS_SLAUGHTER, self::DATE_BASIS_RECORD], true)) {
            $dateBasis = self::DATE_BASIS_SLAUGHTER;
        }

        return [$dateFrom, $dateTo, $dateBasis];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, int|float|null>
     */
    private function sumTotals(Collection $rows): array
    {
        $avgDays = $rows->pluck('avg_cold_room_days')->filter(fn ($v) => $v !== null);
        $avgColdRoom = $avgDays->isNotEmpty() ? round($avgDays->avg(), 1) : null;

        return [
            'animals' => (int) $rows->sum('animals'),
            'total_meat_kg' => (float) $rows->sum('total_meat_kg'),
            'condemned' => (int) $rows->sum('condemned'),
            'certificates' => (int) $rows->sum('certificates'),
            'awaiting_certificate' => (int) $rows->sum('awaiting_certificate'),
            'avg_cold_room_days' => $avgColdRoom,
            'temperature_violations' => (int) $rows->sum('temperature_violations'),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function sortRows(Collection $rows, string $sort, string $direction): Collection
    {
        $allowed = [
            'facility_name', 'operator', 'animals', 'total_meat_kg', 'condemned',
            'certificates', 'awaiting_certificate', 'avg_cold_room_days', 'temperature_violations',
        ];

        if (! in_array($sort, $allowed, true)) {
            $sort = 'facility_name';
        }

        return $rows->sortBy(function (array $row) use ($sort) {
            if ($sort === 'facility_name') {
                return $row['facility']->facility_name ?? '';
            }
            if ($sort === 'operator') {
                return $row['facility']->business->business_name ?? '';
            }

            return $row[$sort] ?? 0;
        }, SORT_REGULAR, $direction === 'desc')->values();
    }
}
