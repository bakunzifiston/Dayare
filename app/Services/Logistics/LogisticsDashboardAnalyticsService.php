<?php

namespace App\Services\Logistics;

use App\Models\LogisticsDriver;
use App\Models\LogisticsInvoice;
use App\Models\LogisticsOrder;
use App\Models\LogisticsTrip;
use App\Models\LogisticsVehicle;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LogisticsDashboardAnalyticsService
{
    public const TREND_DAYS = 30;

    /** @var list<string> */
    private const TRIP_TREND_STATUSES = [
        LogisticsTrip::STATUS_SCHEDULED,
        LogisticsTrip::STATUS_LOADED,
        LogisticsTrip::STATUS_IN_TRANSIT,
        LogisticsTrip::STATUS_AT_CHECKPOINT,
        LogisticsTrip::STATUS_DELAYED,
        LogisticsTrip::STATUS_COMPLETED,
        LogisticsTrip::STATUS_CANCELLED,
    ];

    /**
     * @return array{
     *   trend_days: int,
     *   trend_labels: list<string>,
     *   kpis: array<string, int|float|string>,
     *   efficiency: array<string, float|string|null>,
     *   dashboard_charts: array<string, mixed>,
     *   insights: array<string, mixed>
     * }
     */
    public function forCompany(int $companyId): array
    {
        $trendDays = self::TREND_DAYS;
        $end = Carbon::now()->endOfDay();
        $start = Carbon::now()->subDays($trendDays - 1)->startOfDay();

        $trendLabels = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $trendLabels[] = $d->format('M j');
        }

        $ordersBase = LogisticsOrder::query()->where('company_id', $companyId);
        $tripsBase = LogisticsTrip::query()->where('company_id', $companyId);
        $vehiclesBase = LogisticsVehicle::query()->where('company_id', $companyId);
        $driversBase = LogisticsDriver::query()->where('company_id', $companyId);

        $totalOrders = (clone $ordersBase)->count();
        $ordersInProgress = (clone $ordersBase)->where('status', LogisticsOrder::STATUS_IN_PROGRESS)->count();
        $ordersCompleted = (clone $ordersBase)->where('status', LogisticsOrder::STATUS_COMPLETED)->count();

        $activeTrips = (clone $tripsBase)->whereIn('status', LogisticsTrip::EXECUTION_ACTIVE_STATUSES)->count();
        $completedTrips = (clone $tripsBase)->where('status', LogisticsTrip::STATUS_COMPLETED)->count();
        $delayedTrips = (clone $tripsBase)->where('status', LogisticsTrip::STATUS_DELAYED)->count();

        $totalVehicles = (clone $vehiclesBase)->count();
        $maintenanceVehicles = (clone $vehiclesBase)->where('status', LogisticsVehicle::STATUS_MAINTENANCE)->count();

        $activeVehicleIds = (clone $tripsBase)
            ->whereIn('status', LogisticsTrip::EXECUTION_ACTIVE_STATUSES)
            ->whereNotNull('vehicle_id')
            ->pluck('vehicle_id')
            ->unique()
            ->values();
        $activeVehicles = $activeVehicleIds->count();

        $idleVehicles = $totalVehicles - $activeVehicles;

        $totalDrivers = (clone $driversBase)->count();
        $activeDriverIds = (clone $tripsBase)
            ->whereIn('status', LogisticsTrip::EXECUTION_ACTIVE_STATUSES)
            ->whereNotNull('driver_id')
            ->pluck('driver_id')
            ->unique()
            ->values();
        $activeDrivers = $activeDriverIds->count();
        $availableDrivers = max(0, $totalDrivers - $activeDrivers);

        $invoiceScope = LogisticsInvoice::query()->whereHas(
            'order',
            fn ($q) => $q->where('company_id', $companyId)
        );

        $totalRevenue = (clone $invoiceScope)
            ->where('payment_status', LogisticsInvoice::PAYMENT_PAID)
            ->sum('total_amount');

        $pendingPayments = (clone $invoiceScope)
            ->whereIn('payment_status', [
                LogisticsInvoice::PAYMENT_PENDING,
                LogisticsInvoice::PAYMENT_PARTIALLY_PAID,
                LogisticsInvoice::PAYMENT_OVERDUE,
            ])
            ->sum('total_amount');

        $vehicleUtilPct = $totalVehicles > 0 ? round(100 * $activeVehicles / $totalVehicles, 1) : null;
        $driverUtilPct = $totalDrivers > 0 ? round(100 * $activeDrivers / $totalDrivers, 1) : null;

        $completedForDuration = LogisticsTrip::query()
            ->where('company_id', $companyId)
            ->where('status', LogisticsTrip::STATUS_COMPLETED)
            ->whereNotNull('actual_departure')
            ->whereNotNull('actual_arrival')
            ->get(['actual_departure', 'actual_arrival']);

        $avgTripDurationHours = null;
        if ($completedForDuration->isNotEmpty()) {
            $avgSeconds = $completedForDuration->avg(
                fn (LogisticsTrip $t) => (float) $t->actual_arrival->diffInSeconds($t->actual_departure)
            );
            if ($avgSeconds !== null) {
                $avgTripDurationHours = round(((float) $avgSeconds) / 3600, 2);
            }
        }

        $completedForPunctuality = LogisticsTrip::query()
            ->where('company_id', $companyId)
            ->where('status', LogisticsTrip::STATUS_COMPLETED)
            ->whereNotNull('planned_arrival')
            ->whereNotNull('actual_arrival')
            ->get(['planned_arrival', 'actual_arrival']);

        $onTimePct = null;
        if ($completedForPunctuality->isNotEmpty()) {
            $onTime = $completedForPunctuality->filter(
                fn (LogisticsTrip $t) => $t->actual_arrival->lte($t->planned_arrival)
            )->count();
            $onTimePct = round(100 * $onTime / $completedForPunctuality->count(), 1);
        }

        $ordersTrend = $this->ordersCreatedByDay($companyId, $start, $end, $trendDays);
        $tripsTrendMatrix = $this->tripsByStatusByDay($companyId, $start, $end);
        $revenueTrend = $this->paidInvoiceTotalsByDay($companyId, $start, $end, $trendDays);
        $vehicleTrend = $this->vehicleUtilizationSeries($companyId, $start, $end, $totalVehicles);
        $driverTrend = $this->driverActivitySeries($companyId, $start, $end, $totalDrivers);

        $statusLabels = [
            LogisticsTrip::STATUS_SCHEDULED => __('Scheduled'),
            LogisticsTrip::STATUS_LOADED => __('Loaded'),
            LogisticsTrip::STATUS_IN_TRANSIT => __('In transit'),
            LogisticsTrip::STATUS_AT_CHECKPOINT => __('At checkpoint'),
            LogisticsTrip::STATUS_DELAYED => __('Delayed'),
            LogisticsTrip::STATUS_COMPLETED => __('Completed'),
            LogisticsTrip::STATUS_CANCELLED => __('Cancelled'),
        ];

        $tripChartDatasets = [];
        $tripColors = [
            'rgb(113, 128, 150)',
            'rgb(214, 158, 46)',
            'rgb(161, 29, 30)',
            'rgb(56, 161, 105)',
            'rgb(139, 92, 246)',
            'rgb(59, 130, 246)',
            'rgb(100, 116, 139)',
        ];
        $i = 0;
        foreach (self::TRIP_TREND_STATUSES as $status) {
            $tripChartDatasets[] = [
                'label' => $statusLabels[$status] ?? $status,
                'data' => $tripsTrendMatrix[$status] ?? array_fill(0, $trendDays, 0),
                'backgroundColor' => $tripColors[$i % count($tripColors)],
            ];
            $i++;
        }

        $dashboardCharts = [
            'orders_trend' => [
                'type' => 'line',
                'labels' => $trendLabels,
                'datasets' => [
                    [
                        'label' => __('Orders created'),
                        'data' => $ordersTrend,
                        'fill' => true,
                    ],
                ],
            ],
            'trips_trend' => [
                'type' => 'bar',
                'stacked' => true,
                'labels' => $trendLabels,
                'datasets' => $tripChartDatasets,
            ],
            'revenue_trend' => [
                'type' => 'line',
                'labels' => $trendLabels,
                'yTickPrecision' => 2,
                'datasets' => [
                    [
                        'label' => __('Paid revenue'),
                        'data' => $revenueTrend,
                        'fill' => true,
                    ],
                ],
            ],
            'vehicle_utilization_trend' => [
                'type' => 'line',
                'labels' => $trendLabels,
                'datasets' => [
                    [
                        'label' => __('Active vehicles'),
                        'data' => $vehicleTrend['active'],
                    ],
                    [
                        'label' => __('Idle vehicles'),
                        'data' => $vehicleTrend['idle'],
                    ],
                ],
            ],
            'driver_activity_trend' => [
                'type' => 'line',
                'labels' => $trendLabels,
                'datasets' => [
                    [
                        'label' => __('Active drivers'),
                        'data' => $driverTrend['active'],
                    ],
                    [
                        'label' => __('Available drivers'),
                        'data' => $driverTrend['available'],
                    ],
                ],
            ],
        ];

        return [
            'trend_days' => $trendDays,
            'trend_labels' => $trendLabels,
            'kpis' => [
                'orders_total' => $totalOrders,
                'orders_in_progress' => $ordersInProgress,
                'orders_completed' => $ordersCompleted,
                'trips_active' => $activeTrips,
                'trips_completed' => $completedTrips,
                'trips_delayed' => $delayedTrips,
                'vehicles_total' => $totalVehicles,
                'vehicles_active' => $activeVehicles,
                'vehicles_idle' => max(0, $idleVehicles),
                'vehicles_maintenance' => $maintenanceVehicles,
                'drivers_total' => $totalDrivers,
                'drivers_active' => $activeDrivers,
                'drivers_available' => $availableDrivers,
                'revenue_paid' => (float) $totalRevenue,
                'revenue_pending' => (float) $pendingPayments,
            ],
            'efficiency' => [
                'vehicle_utilization_pct' => $vehicleUtilPct,
                'driver_utilization_pct' => $driverUtilPct,
                'avg_trip_duration_hours' => $avgTripDurationHours,
                'on_time_delivery_pct' => $onTimePct,
            ],
            'dashboard_charts' => $dashboardCharts,
            'insights' => [
                'top_vehicles' => $this->topVehiclesByTrips($companyId),
                'idle_vehicles' => $this->idleVehicleRows($companyId, $activeVehicleIds),
                'top_drivers' => $this->topDriversByTrips($companyId),
                'drivers_with_issues' => $this->driversWithDelayIssues($companyId),
            ],
        ];
    }

    /**
     * @return list<int>
     */
    private function ordersCreatedByDay(int $companyId, Carbon $start, Carbon $end, int $days): array
    {
        $counts = array_fill(0, $days, 0);
        $rows = LogisticsOrder::query()
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupByRaw('DATE(created_at)')
            ->pluck('c', 'd');

        $idx = 0;
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $key = $d->format('Y-m-d');
            $counts[$idx] = (int) ($rows[$key] ?? 0);
            $idx++;
        }

        return $counts;
    }

    /**
     * @return array<string, list<int>>
     */
    private function tripsByStatusByDay(int $companyId, Carbon $start, Carbon $end): array
    {
        $days = self::TREND_DAYS;
        $matrix = [];
        foreach (self::TRIP_TREND_STATUSES as $status) {
            $matrix[$status] = array_fill(0, $days, 0);
        }

        $trips = LogisticsTrip::query()
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$start, $end])
            ->get(['created_at', 'status']);

        foreach ($trips as $trip) {
            $dayIndex = $start->diffInDays($trip->created_at->copy()->startOfDay());
            if ($dayIndex < 0 || $dayIndex >= $days) {
                continue;
            }
            $status = $trip->status;
            if (! isset($matrix[$status])) {
                continue;
            }
            $matrix[$status][$dayIndex]++;
        }

        return $matrix;
    }

    /**
     * @return list<float>
     */
    private function paidInvoiceTotalsByDay(int $companyId, Carbon $start, Carbon $end, int $days): array
    {
        $totals = array_fill(0, $days, 0.0);
        $rows = LogisticsInvoice::query()
            ->whereHas('order', fn ($q) => $q->where('company_id', $companyId))
            ->where('payment_status', LogisticsInvoice::PAYMENT_PAID)
            ->whereBetween('issued_at', [$start, $end])
            ->selectRaw('DATE(issued_at) as d, SUM(total_amount) as s')
            ->groupByRaw('DATE(issued_at)')
            ->get();

        foreach ($rows as $row) {
            $day = Carbon::parse($row->d)->startOfDay();
            $idx = $start->diffInDays($day);
            if ($idx >= 0 && $idx < $days) {
                $totals[$idx] = round((float) $row->s, 2);
            }
        }

        return $totals;
    }

    /**
     * @return array{active: list<int>, idle: list<int>}
     */
    private function vehicleUtilizationSeries(int $companyId, Carbon $start, Carbon $end, int $totalVehicles): array
    {
        $days = self::TREND_DAYS;
        $active = array_fill(0, $days, 0);
        $idle = array_fill(0, $days, 0);

        $idx = 0;
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $dayStart = $d->copy()->startOfDay();
            $dayEnd = $d->copy()->endOfDay();
            $n = $this->distinctResourcesOnExecutionTripsOverlappingDay(
                $companyId,
                $dayStart,
                $dayEnd,
                'vehicle_id'
            );
            $active[$idx] = $n;
            $idle[$idx] = max(0, $totalVehicles - $n);
            $idx++;
        }

        return ['active' => $active, 'idle' => $idle];
    }

    /**
     * @return array{active: list<int>, available: list<int>}
     */
    private function driverActivitySeries(int $companyId, Carbon $start, Carbon $end, int $totalDrivers): array
    {
        $days = self::TREND_DAYS;
        $active = array_fill(0, $days, 0);
        $available = array_fill(0, $days, 0);

        $idx = 0;
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $dayStart = $d->copy()->startOfDay();
            $dayEnd = $d->copy()->endOfDay();
            $n = $this->distinctResourcesOnExecutionTripsOverlappingDay(
                $companyId,
                $dayStart,
                $dayEnd,
                'driver_id'
            );
            $active[$idx] = $n;
            $available[$idx] = max(0, $totalDrivers - $n);
            $idx++;
        }

        return ['active' => $active, 'available' => $available];
    }

    private function distinctResourcesOnExecutionTripsOverlappingDay(
        int $companyId,
        Carbon $dayStart,
        Carbon $dayEnd,
        string $column
    ): int {
        if (! in_array($column, ['vehicle_id', 'driver_id'], true)) {
            return 0;
        }

        return LogisticsTrip::query()
            ->where('company_id', $companyId)
            ->whereIn('status', LogisticsTrip::EXECUTION_ACTIVE_STATUSES)
            ->whereNotNull($column)
            ->where(function ($q) use ($dayStart, $dayEnd) {
                $q->whereRaw('COALESCE(actual_departure, planned_departure) <= ?', [$dayEnd])
                    ->where(function ($q2) use ($dayStart) {
                        $q2->whereNull('actual_arrival')
                            ->orWhere('actual_arrival', '>=', $dayStart);
                    });
            })
            ->pluck($column)
            ->unique()
            ->filter()
            ->count();
    }

    /**
     * @return Collection<int, object{vehicle_id: int, plate_number: string, trip_count: int}>
     */
    private function topVehiclesByTrips(int $companyId): Collection
    {
        return LogisticsTrip::query()
            ->where('company_id', $companyId)
            ->whereNotNull('vehicle_id')
            ->selectRaw('vehicle_id, COUNT(*) as trip_count')
            ->groupBy('vehicle_id')
            ->orderByDesc('trip_count')
            ->limit(8)
            ->get()
            ->map(function ($row) {
                $v = LogisticsVehicle::query()->find($row->vehicle_id);

                return (object) [
                    'vehicle_id' => (int) $row->vehicle_id,
                    'plate_number' => $v?->plate_number ?? '—',
                    'trip_count' => (int) $row->trip_count,
                ];
            });
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $activeVehicleIds
     * @return Collection<int, object{vehicle_id: int, plate_number: string, status: string}>
     */
    private function idleVehicleRows(int $companyId, Collection $activeVehicleIds): Collection
    {
        $q = LogisticsVehicle::query()
            ->where('company_id', $companyId)
            ->orderBy('plate_number');

        if ($activeVehicleIds->isNotEmpty()) {
            $q->whereNotIn('id', $activeVehicleIds->all());
        }

        return $q->limit(12)->get()->map(fn (LogisticsVehicle $v) => (object) [
            'vehicle_id' => $v->id,
            'plate_number' => $v->plate_number,
            'status' => $v->status,
        ]);
    }

    /**
     * @return Collection<int, object{driver_id: int, name: string, trip_count: int}>
     */
    private function topDriversByTrips(int $companyId): Collection
    {
        return LogisticsTrip::query()
            ->where('company_id', $companyId)
            ->whereNotNull('driver_id')
            ->selectRaw('driver_id, COUNT(*) as trip_count')
            ->groupBy('driver_id')
            ->orderByDesc('trip_count')
            ->limit(8)
            ->get()
            ->map(function ($row) {
                $d = LogisticsDriver::query()->find($row->driver_id);
                $name = $d ? trim(($d->first_name ?? '').' '.($d->last_name ?? '')) : '';
                if ($name === '') {
                    $name = $d?->name ?? '—';
                }

                return (object) [
                    'driver_id' => (int) $row->driver_id,
                    'name' => $name,
                    'trip_count' => (int) $row->trip_count,
                ];
            });
    }

    /**
     * Drivers linked to delayed trips or late completed arrivals.
     *
     * @return Collection<int, object{driver_id: int, name: string, delayed_trips: int, late_completions: int}>
     */
    private function driversWithDelayIssues(int $companyId): Collection
    {
        $delayedByDriver = LogisticsTrip::query()
            ->where('company_id', $companyId)
            ->where('status', LogisticsTrip::STATUS_DELAYED)
            ->whereNotNull('driver_id')
            ->selectRaw('driver_id, COUNT(*) as c')
            ->groupBy('driver_id')
            ->pluck('c', 'driver_id');

        $lateRows = LogisticsTrip::query()
            ->where('company_id', $companyId)
            ->where('status', LogisticsTrip::STATUS_COMPLETED)
            ->whereNotNull('driver_id')
            ->whereNotNull('planned_arrival')
            ->whereNotNull('actual_arrival')
            ->whereColumn('actual_arrival', '>', 'planned_arrival')
            ->selectRaw('driver_id, COUNT(*) as c')
            ->groupBy('driver_id')
            ->pluck('c', 'driver_id');

        $driverIds = $delayedByDriver->keys()->merge($lateRows->keys())->unique()->values();

        return $driverIds->map(function ($driverId) use ($delayedByDriver, $lateRows) {
            $d = LogisticsDriver::query()->find($driverId);
            $name = $d ? trim(($d->first_name ?? '').' '.($d->last_name ?? '')) : '';
            if ($name === '') {
                $name = $d?->name ?? '—';
            }

            return (object) [
                'driver_id' => (int) $driverId,
                'name' => $name,
                'delayed_trips' => (int) ($delayedByDriver[$driverId] ?? 0),
                'late_completions' => (int) ($lateRows[$driverId] ?? 0),
            ];
        })
            ->sortByDesc(fn ($row) => $row->delayed_trips + $row->late_completions)
            ->values()
            ->take(12);
    }
}
