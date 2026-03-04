<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Certificate;
use App\Models\DeliveryConfirmation;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\SlaughterExecution;
use App\Models\SlaughterPlan;
use App\Models\TransportTrip;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the authenticated user's (tenant's) dashboard.
     * Each user only sees their own dashboard and data.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $businessIds = $user->businesses()->pluck('id');

        if ($businessIds->isEmpty()) {
            $kpis = [
                'businesses' => 0,
                'facilities' => 0,
                'inspectors' => 0,
                'slaughter_plans' => 0,
                'slaughter_plans_planned' => 0,
                'slaughter_plans_approved' => 0,
                'slaughter_executions' => 0,
                'executions_completed' => 0,
                'batches' => 0,
                'certificates' => 0,
                'certificates_active' => 0,
                'transport_trips' => 0,
                'delivery_confirmations' => 0,
            ];
            $charts = $this->buildChartDataEmpty();

            return view('dashboard', ['user' => $user, 'kpis' => $kpis, 'charts' => $charts]);
        }

        $facilityIds = Facility::whereIn('business_id', $businessIds)->pluck('id');

        $plans = SlaughterPlan::whereIn('facility_id', $facilityIds);
        $planIds = $plans->pluck('id');
        $executions = SlaughterExecution::whereIn('slaughter_plan_id', $planIds);
        $executionIds = $executions->pluck('id');
        $batches = Batch::whereIn('slaughter_execution_id', $executionIds);
        $batchIds = $batches->pluck('id');
        $certificates = Certificate::whereIn('batch_id', $batchIds);
        $certificateIds = $certificates->pluck('id');
        $trips = TransportTrip::whereIn('certificate_id', $certificateIds);
        $tripIds = $trips->pluck('id');

        $kpis = [
            'businesses' => $businessIds->count(),
            'facilities' => $facilityIds->count(),
            'inspectors' => Inspector::whereIn('facility_id', $facilityIds)->count(),
            'slaughter_plans' => $planIds->count(),
            'slaughter_plans_planned' => (clone $plans)->where('status', SlaughterPlan::STATUS_PLANNED)->count(),
            'slaughter_plans_approved' => (clone $plans)->where('status', SlaughterPlan::STATUS_APPROVED)->count(),
            'slaughter_executions' => $executionIds->count(),
            'executions_completed' => (clone $executions)->where('status', SlaughterExecution::STATUS_COMPLETED)->count(),
            'batches' => $batchIds->count(),
            'certificates' => $certificateIds->count(),
            'certificates_active' => (clone $certificates)->where('status', Certificate::STATUS_ACTIVE)->count(),
            'transport_trips' => $tripIds->count(),
            'delivery_confirmations' => DeliveryConfirmation::whereIn('transport_trip_id', $tripIds)->count(),
        ];

        $charts = $this->buildChartData($facilityIds, $planIds, $executionIds, $batchIds, $certificateIds);

        return view('dashboard', [
            'user' => $user,
            'kpis' => $kpis,
            'charts' => $charts,
        ]);
    }

    /**
     * Empty chart structure (no data).
     */
    private function buildChartDataEmpty(): array
    {
        $months = collect();
        $now = Carbon::now();
        for ($i = 5; $i >= 0; $i--) {
            $months->push($now->copy()->subMonths($i));
        }
        $labels = $months->map(fn ($d) => $d->locale(app()->getLocale())->translatedFormat('M Y'))->values()->all();
        $zeros = array_fill(0, count($labels), 0);

        return [
            'slaughter_plans' => ['labels' => $labels, 'datasets' => [['label' => __('Slaughter plans'), 'data' => $zeros]], 'type' => 'bar'],
            'certificates' => ['labels' => $labels, 'datasets' => [['label' => __('Certificates issued'), 'data' => $zeros]], 'type' => 'bar'],
            'batches_executions' => ['labels' => $labels, 'datasets' => [['label' => __('Batches'), 'data' => $zeros], ['label' => __('Executions'), 'data' => $zeros]], 'type' => 'line'],
        ];
    }

    /**
     * Build monthly trend data for the last 6 months (DB-agnostic).
     */
    private function buildChartData($facilityIds, $planIds, $executionIds, $batchIds, $certificateIds): array
    {
        $months = collect();
        $now = Carbon::now();
        for ($i = 5; $i >= 0; $i--) {
            $months->push($now->copy()->subMonths($i));
        }
        $labels = $months->map(fn ($d) => $d->locale(app()->getLocale())->translatedFormat('M Y'))->values()->all();
        $monthKeys = $months->map(fn ($d) => $d->format('Y-m'))->values()->all();
        $fill = fn (array $data) => array_map(fn ($key) => $data[$key] ?? 0, $monthKeys);

        $start = $months->first()->startOfMonth()->toDateString();
        $end = $months->last()->endOfMonth()->toDateString();

        $slaughterPlansByMonth = SlaughterPlan::whereIn('facility_id', $facilityIds)
            ->whereBetween('slaughter_date', [$start, $end])
            ->get()
            ->groupBy(fn ($p) => Carbon::parse($p->slaughter_date)->format('Y-m'))
            ->map->count()
            ->all();

        $certificatesByMonth = Certificate::whereIn('batch_id', $batchIds)
            ->whereBetween('issued_at', [$start, $end])
            ->get()
            ->groupBy(fn ($c) => Carbon::parse($c->issued_at)->format('Y-m'))
            ->map->count()
            ->all();

        $batchesByMonth = Batch::whereIn('id', $batchIds)
            ->whereBetween('created_at', [$start, $end])
            ->get()
            ->groupBy(fn ($b) => Carbon::parse($b->created_at)->format('Y-m'))
            ->map->count()
            ->all();

        $executionsByMonth = SlaughterExecution::whereIn('id', $executionIds)
            ->whereBetween('slaughter_time', [$start, $end])
            ->get()
            ->groupBy(fn ($e) => Carbon::parse($e->slaughter_time)->format('Y-m'))
            ->map->count()
            ->all();

        return [
            'slaughter_plans' => [
                'labels' => $labels,
                'datasets' => [
                    ['label' => __('Slaughter plans'), 'data' => $fill($slaughterPlansByMonth)],
                ],
                'type' => 'bar',
            ],
            'certificates' => [
                'labels' => $labels,
                'datasets' => [
                    ['label' => __('Certificates issued'), 'data' => $fill($certificatesByMonth)],
                ],
                'type' => 'bar',
            ],
            'batches_executions' => [
                'labels' => $labels,
                'datasets' => [
                    ['label' => __('Batches'), 'data' => $fill($batchesByMonth)],
                    ['label' => __('Executions'), 'data' => $fill($executionsByMonth)],
                ],
                'type' => 'line',
            ],
        ];
    }
}
