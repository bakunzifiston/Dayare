<?php

namespace App\Http\Controllers;

use App\Models\AnimalIntake;
use App\Models\AnteMortemInspection;
use App\Models\Batch;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Certificate;
use App\Models\DeliveryConfirmation;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\PostMortemInspection;
use App\Models\SlaughterExecution;
use App\Models\SlaughterPlan;
use App\Models\TemperatureLog;
use App\Models\TransportTrip;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        if ($user->isSuperAdmin()) {
            return redirect()->route('super-admin.dashboard');
        }

        $activeBusinessId = $user->activeProcessorBusinessId();
        $role = $user->processorRoleForBusiness($activeBusinessId);

        if ($activeBusinessId === null || $role === null) {
            return view('dashboard', [
                'user' => $user,
                'role' => $role,
                'activeBusiness' => null,
                'metrics' => [],
                'alerts' => [],
                'quickActions' => [],
                'mapSummary' => [
                    'facilities' => 0,
                    'active_routes' => 0,
                ],
                'kpiPeriod' => 'all',
                'kpiPeriodLabel' => '',
                'accountantSnapshot' => null,
            ]);
        }

        $user->setActiveProcessorBusinessId($activeBusinessId);

        $business = Business::query()->find($activeBusinessId);
        $data = $this->buildRoleDashboardData($request, $role, $activeBusinessId);

        return view('dashboard', [
            'user' => $user,
            'role' => $role,
            'activeBusiness' => $business,
            'metrics' => $data['metrics'],
            'alerts' => $data['alerts'],
            'quickActions' => $data['quickActions'],
            'mapSummary' => $data['mapSummary'],
            'kpiPeriod' => $data['kpiPeriod'] ?? 'all',
            'kpiPeriodLabel' => $data['kpiPeriodLabel'] ?? '',
            'accountantSnapshot' => $data['accountantSnapshot'] ?? null,
        ]);
    }

    /**
     * @return array{
     *   metrics: array<int, array<string, string|int>>,
     *   alerts: array<int, array<string, string|int>>,
     *   quickActions: array<int, array<string, string>>,
     *   mapSummary: array{facilities: int, active_routes: int},
     *   kpiPeriod: string,
     *   kpiPeriodLabel: string
     * }
     */
    private function buildRoleDashboardData(Request $request, string $role, int $businessId): array
    {
        $kpiPeriod = (string) $request->query('kpi_period', 'all');
        if (! in_array($kpiPeriod, ['all', 'day', 'month', 'year'], true)) {
            $kpiPeriod = 'all';
        }

        if ($role === BusinessUser::ROLE_ACCOUNTANT) {
            return $this->buildAccountantDashboardData($request, $businessId, $kpiPeriod);
        }

        $today = now()->startOfDay();
        $soonDate = now()->addDays(7)->endOfDay();

        $facilityIds = Facility::query()
            ->where('business_id', $businessId)
            ->pluck('id');
        $planIds = SlaughterPlan::query()->whereIn('facility_id', $facilityIds)->pluck('id');
        $executionIds = SlaughterExecution::query()->whereIn('slaughter_plan_id', $planIds)->pluck('id');
        $batchIds = Batch::query()->whereIn('slaughter_execution_id', $executionIds)->pluck('id');
        $certificateIds = Certificate::query()->whereIn('batch_id', $batchIds)->pluck('id');
        $tripIds = TransportTrip::query()->whereIn('certificate_id', $certificateIds)->pluck('id');
        $facilitiesCount = (int) $facilityIds->count();

        $totalBatches = Batch::query()->whereIn('id', $batchIds)->count();
        $batchesToday = Batch::query()->whereIn('id', $batchIds)->whereDate('created_at', $today)->count();
        $pendingInspectionBatches = Batch::query()->whereIn('id', $batchIds)->whereDoesntHave('postMortemInspection')->count();
        $unassignedBatches = Batch::query()->whereIn('id', $batchIds)->whereNull('inspector_id')->count();
        $overdueInspections = Batch::query()
            ->whereIn('id', $batchIds)
            ->whereDoesntHave('postMortemInspection')
            ->whereDate('created_at', '<', now()->subDay())
            ->count();

        $certificatesIssued = Certificate::query()->whereIn('id', $certificateIds)->count();
        $validCertificates = Certificate::query()->whereIn('id', $certificateIds)->compliant()->count();
        $expiredCertificates = Certificate::query()
            ->whereIn('id', $certificateIds)
            ->where(function ($query): void {
                $query->where('status', Certificate::STATUS_EXPIRED)
                    ->orWhere(function ($q): void {
                        $q->whereNotNull('expiry_date')->where('expiry_date', '<', now()->startOfDay());
                    });
            })->count();
        $expiringCertificates = Certificate::query()
            ->whereIn('id', $certificateIds)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now()->startOfDay(), $soonDate])
            ->count();

        $nonComplianceIssues = (
            SlaughterPlan::query()->whereIn('id', $planIds)->where('status', SlaughterPlan::STATUS_PLANNED)->whereDate('slaughter_date', '<', $today)->count()
            + $pendingInspectionBatches
            + TemperatureLog::query()
                ->whereIn('warehouse_storage_id', \App\Models\WarehouseStorage::query()->whereIn('certificate_id', $certificateIds)->pluck('id'))
                ->whereIn('status', [TemperatureLog::STATUS_WARNING, TemperatureLog::STATUS_CRITICAL])
                ->count()
        );
        if ($kpiPeriod === 'all') {
            $kpiPeriodLabel = (string) __('All time');
            $kpiShortLabel = (string) __('All time');
            $animalsProcessedKpi = (int) SlaughterExecution::query()
                ->whereIn('id', $executionIds)
                ->whereNotNull('slaughter_time')
                ->sum('actual_animals_slaughtered');
            $batchesInKpi = $totalBatches;
            $certificatesIssuedKpi = $certificatesIssued;
            $validCertificatesKpi = $validCertificates;
            $otherCertificatesKpi = max(0, $certificatesIssued - $validCertificates);
        } else {
            $range = $this->kpiDateRange($kpiPeriod);
            $rangeStart = $range['start'];
            $rangeEnd = $range['end'];
            $kpiPeriodLabel = $range['label'];
            $kpiShortLabel = $range['shortLabel'];
            $animalsProcessedKpi = (int) SlaughterExecution::query()
                ->whereIn('id', $executionIds)
                ->whereNotNull('slaughter_time')
                ->whereBetween('slaughter_time', [$rangeStart, $rangeEnd])
                ->sum('actual_animals_slaughtered');
            $batchesInKpi = Batch::query()
                ->whereIn('id', $batchIds)
                ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                ->count();
            $certificatesIssuedKpi = Certificate::query()
                ->whereIn('id', $certificateIds)
                ->whereBetween('issued_at', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
                ->count();
            $validCertificatesKpi = Certificate::query()
                ->whereIn('id', $certificateIds)
                ->whereBetween('issued_at', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
                ->compliant()
                ->count();
            $otherCertificatesKpi = max(0, $certificatesIssuedKpi - $validCertificatesKpi);
        }

        $intakeQueue = AnimalIntake::query()
            ->whereIn('facility_id', $facilityIds)
            ->whereIn('status', [AnimalIntake::STATUS_RECEIVED, AnimalIntake::STATUS_APPROVED])
            ->count();
        $slaughterScheduledToday = SlaughterPlan::query()
            ->whereIn('id', $planIds)
            ->whereDate('slaughter_date', $today)
            ->count();
        $schedulingDelays = SlaughterPlan::query()
            ->whereIn('id', $planIds)
            ->where('status', SlaughterPlan::STATUS_PLANNED)
            ->whereDate('slaughter_date', '<', $today)
            ->count();
        $processingBottlenecks = $pendingInspectionBatches;

        $anteToday = AnteMortemInspection::query()
            ->whereIn('slaughter_plan_id', $planIds)
            ->whereDate('inspection_date', $today)
            ->count();
        $postToday = PostMortemInspection::query()
            ->whereIn('batch_id', $batchIds)
            ->whereDate('inspection_date', $today)
            ->count();
        $inspectionsCompletedToday = $anteToday + $postToday;
        $pendingInspections = $pendingInspectionBatches;
        $inspectorCapacity = (int) Inspector::query()->whereIn('facility_id', $facilityIds)->sum('daily_capacity');
        $remainingCapacity = max(0, $inspectorCapacity - $inspectionsCompletedToday);
        $failedInspections = PostMortemInspection::query()
            ->whereIn('batch_id', $batchIds)
            ->where('result', PostMortemInspection::RESULT_REJECTED)
            ->count();
        $capacityOverload = max(0, $inspectionsCompletedToday - $inspectorCapacity);

        $totalChecklistTasks = max(1, SlaughterPlan::query()->whereIn('id', $planIds)->count() + Batch::query()->whereIn('id', $batchIds)->count());
        $completedChecklists = AnteMortemInspection::query()->whereIn('slaughter_plan_id', $planIds)->count()
            + PostMortemInspection::query()->whereIn('batch_id', $batchIds)->count();
        $checklistCompletionRate = (int) round((min($completedChecklists, $totalChecklistTasks) / $totalChecklistTasks) * 100);
        $openComplianceIssues = $nonComplianceIssues;
        $resolvedCompliance = max(0, $totalChecklistTasks - $openComplianceIssues);
        $resolutionRate = (int) round(($resolvedCompliance / max(1, $totalChecklistTasks)) * 100);
        $totalTemps = TemperatureLog::query()
            ->whereIn('warehouse_storage_id', \App\Models\WarehouseStorage::query()->whereIn('certificate_id', $certificateIds)->pluck('id'))
            ->count();
        $normalTemps = TemperatureLog::query()
            ->whereIn('warehouse_storage_id', \App\Models\WarehouseStorage::query()->whereIn('certificate_id', $certificateIds)->pluck('id'))
            ->where('status', TemperatureLog::STATUS_NORMAL)
            ->count();
        $temperatureComplianceRate = $totalTemps > 0 ? (int) round(($normalTemps / $totalTemps) * 100) : 100;
        $temperatureBreaches = max(0, $totalTemps - $normalTemps);
        $missingChecklistSubmissions = max(0, $totalChecklistTasks - min($completedChecklists, $totalChecklistTasks));

        $activeTrips = TransportTrip::query()
            ->whereIn('id', $tripIds)
            ->whereIn('status', [TransportTrip::STATUS_PENDING, TransportTrip::STATUS_IN_TRANSIT, TransportTrip::STATUS_ARRIVED])
            ->count();
        $deliveriesCompleted = DeliveryConfirmation::query()
            ->whereIn('transport_trip_id', $tripIds)
            ->where('confirmation_status', DeliveryConfirmation::STATUS_CONFIRMED)
            ->count();
        $deliveriesPending = TransportTrip::query()
            ->whereIn('id', $tripIds)
            ->where(function ($query): void {
                $query->whereDoesntHave('deliveryConfirmation')
                    ->orWhereHas('deliveryConfirmation', fn ($q) => $q->where('confirmation_status', DeliveryConfirmation::STATUS_PENDING));
            })
            ->count();
        $onTimeDeliveries = TransportTrip::query()
            ->whereIn('id', $tripIds)
            ->whereHas('deliveryConfirmation', function ($query): void {
                $query->whereNotNull('received_date')
                    ->whereColumn('received_date', '<=', 'transport_trips.arrival_date');
            })
            ->count();
        $onTimeDeliveryRate = $deliveriesCompleted > 0 ? (int) round(($onTimeDeliveries / $deliveriesCompleted) * 100) : 0;
        $delayedDeliveries = TransportTrip::query()
            ->whereIn('id', $tripIds)
            ->whereIn('status', [TransportTrip::STATUS_PENDING, TransportTrip::STATUS_IN_TRANSIT, TransportTrip::STATUS_ARRIVED])
            ->whereDate('departure_date', '<', now()->subDay())
            ->count();
        $missingCertificateAttachments = TransportTrip::query()
            ->whereIn('id', $tripIds)
            ->where(function ($query): void {
                $query->whereNull('certificate_id')->orWhereDoesntHave('certificate');
            })
            ->count();

        $quickActions = [
            BusinessUser::ROLE_ORG_ADMIN => [
                ['label' => __('Manage users'), 'route' => 'tenant-users.index', 'permission' => BusinessUser::PERMISSION_MANAGE_BUSINESS_USERS],
                ['label' => __('Review compliance'), 'route' => 'compliance.index', 'permission' => BusinessUser::PERMISSION_MONITOR_COMPLIANCE_METRICS],
                ['label' => __('View certificates'), 'route' => 'certificates.index', 'permission' => BusinessUser::PERMISSION_VIEW_CERTIFICATES],
            ],
            BusinessUser::ROLE_OPERATIONS_MANAGER => [
                ['label' => __('Create intake'), 'route' => 'animal-intakes.create', 'permission' => BusinessUser::PERMISSION_CREATE_ANIMAL_INTAKE],
                ['label' => __('Schedule slaughter'), 'route' => 'slaughter-plans.create', 'permission' => BusinessUser::PERMISSION_SCHEDULE_SLAUGHTER],
                ['label' => __('Create batch'), 'route' => 'batches.create', 'permission' => BusinessUser::PERMISSION_CREATE_BATCH],
            ],
            BusinessUser::ROLE_INSPECTOR => [
                ['label' => __('Ante-mortem queue'), 'route' => 'ante-mortem-inspections.index', 'permission' => BusinessUser::PERMISSION_RECORD_ANTE_MORTEM],
                ['label' => __('Post-mortem queue'), 'route' => 'post-mortem-inspections.index', 'permission' => BusinessUser::PERMISSION_RECORD_POST_MORTEM],
                ['label' => __('Issue certificates'), 'route' => 'certificates.create', 'permission' => BusinessUser::PERMISSION_ISSUE_CERTIFICATE],
            ],
            BusinessUser::ROLE_COMPLIANCE_OFFICER => [
                ['label' => __('Open compliance monitor'), 'route' => 'compliance.index', 'permission' => BusinessUser::PERMISSION_MONITOR_COMPLIANCE_METRICS],
                ['label' => __('Review temperature logs'), 'route' => 'warehouse-storages.index', 'permission' => BusinessUser::PERMISSION_MONITOR_TEMPERATURE_LOGS],
                ['label' => __('Review certificates'), 'route' => 'certificates.index', 'permission' => BusinessUser::PERMISSION_VIEW_CERTIFICATES],
            ],
            BusinessUser::ROLE_TRANSPORT_MANAGER => [
                ['label' => __('Create transport trip'), 'route' => 'transport-trips.create', 'permission' => BusinessUser::PERMISSION_CREATE_TRANSPORT_TRIP],
                ['label' => __('Confirm deliveries'), 'route' => 'delivery-confirmations.index', 'permission' => BusinessUser::PERMISSION_CONFIRM_DELIVERY],
                ['label' => __('Review trip statuses'), 'route' => 'transport-trips.index', 'permission' => BusinessUser::PERMISSION_TRACK_DELIVERY_STATUS],
            ],
        ];

        $kpiIsAll = $kpiPeriod === 'all';

        $orgAdminAnimalsDesc = $kpiIsAll
            ? __('Total slaughtered head recorded for this business (all history).')
            : __('Slaughtered head in :range — same throughput window as the filter.', ['range' => $kpiPeriodLabel]);

        $orgAdminBatchesDesc = $kpiIsAll
            ? __('All production batches tied to slaughter runs for this business (same as cumulative total).')
            : __('Production batches with created date in the selected range.');

        $orgAdminCertsLabel = $kpiIsAll
            ? __('Certificates (valid / other)')
            : __('Certificates in period (valid / other)');

        $orgAdminCertsDesc = $kpiIsAll
            ? __('Across every certificate issued for this business. “Other” includes expired, revoked, or not compliant.')
            : __('Issued in :range. “Other” includes expired, revoked, or not yet valid.', ['range' => $kpiPeriodLabel]);

        $metricsByRole = [
            BusinessUser::ROLE_ORG_ADMIN => [
                ['label' => __('Animals processed (:span)', ['span' => $kpiShortLabel]), 'value' => $animalsProcessedKpi, 'description' => $orgAdminAnimalsDesc],
                ['label' => __('Batches created (:span)', ['span' => $kpiShortLabel]), 'value' => $batchesInKpi, 'description' => $orgAdminBatchesDesc],
                ['label' => __('Total batches (all time)'), 'value' => $totalBatches, 'description' => __('Cumulative traceability volume for this business.')],
                ['label' => $orgAdminCertsLabel, 'value' => $validCertificatesKpi.' / '.$otherCertificatesKpi, 'description' => $orgAdminCertsDesc],
            ],
            BusinessUser::ROLE_OPERATIONS_MANAGER => [
                ['label' => __('Animals in intake queue'), 'value' => $intakeQueue, 'description' => __('Prioritize intake processing workload.')],
                ['label' => __('Slaughter scheduled today'), 'value' => $slaughterScheduledToday, 'description' => __('Confirms execution plan for today.')],
                ['label' => __('Batches created today'), 'value' => $batchesToday, 'description' => __('Monitors production conversion speed.')],
                ['label' => __('Batches pending inspection'), 'value' => $pendingInspectionBatches, 'description' => __('Prevents inspection bottlenecks.')],
                ['label' => __('Unassigned batches'), 'value' => $unassignedBatches, 'description' => __('Shows assignment gaps that block inspections.')],
            ],
            BusinessUser::ROLE_INSPECTOR => [
                ['label' => __('Inspections completed today'), 'value' => $inspectionsCompletedToday, 'description' => __('Shows inspection output for the shift.')],
                ['label' => __('Pending inspections'), 'value' => $pendingInspections, 'description' => __('Backlog requiring immediate assignment.')],
                ['label' => __('Certificates issued'), 'value' => $certificatesIssued, 'description' => __('Reflects cleared inspection volume.')],
                ['label' => __('Remaining inspection capacity'), 'value' => $remainingCapacity, 'description' => __('Capacity buffer before overload risk.')],
                ['label' => __('Failed inspections'), 'value' => $failedInspections, 'description' => __('Rejected outcomes that need corrective action.')],
            ],
            BusinessUser::ROLE_COMPLIANCE_OFFICER => [
                ['label' => __('Checklist completion rate'), 'value' => $checklistCompletionRate.'%', 'description' => __('Measures process control discipline.')],
                ['label' => __('Open non-compliance issues'), 'value' => $openComplianceIssues, 'description' => __('Current unresolved compliance risks.')],
                ['label' => __('Resolution rate'), 'value' => $resolutionRate.'%', 'description' => __('Speed of closing compliance findings.')],
                ['label' => __('Temperature compliance'), 'value' => $temperatureComplianceRate.'%', 'description' => __('Cold-chain adherence indicator.')],
                ['label' => __('Temperature breaches'), 'value' => $temperatureBreaches, 'description' => __('Count of warning/critical temperature incidents.')],
            ],
            BusinessUser::ROLE_TRANSPORT_MANAGER => [
                ['label' => __('Active trips'), 'value' => $activeTrips, 'description' => __('Current live logistics workload.')],
                ['label' => __('Deliveries completed'), 'value' => $deliveriesCompleted, 'description' => __('Total confirmed delivery throughput.')],
                ['label' => __('Deliveries pending'), 'value' => $deliveriesPending, 'description' => __('Work queue requiring follow-up.')],
                ['label' => __('On-time delivery rate'), 'value' => $onTimeDeliveryRate.'%', 'description' => __('Reliability and SLA performance.')],
                ['label' => __('Delayed deliveries'), 'value' => $delayedDeliveries, 'description' => __('Trips at risk of missing delivery commitments.')],
            ],
        ];

        $alertsByRole = [
            BusinessUser::ROLE_ORG_ADMIN => [
                ['title' => __('Expiring certificates'), 'count' => $expiringCertificates, 'description' => __('Certificates expiring in 7 days.'), 'route' => 'certificates.index'],
                ['title' => __('High non-compliance rate'), 'count' => $openComplianceIssues, 'description' => __('Open issues requiring governance intervention.'), 'route' => 'compliance.index'],
                ['title' => __('Inspection backlog'), 'count' => $pendingInspectionBatches, 'description' => __('Batches waiting for post-mortem inspection.'), 'route' => 'post-mortem-inspections.index'],
            ],
            BusinessUser::ROLE_OPERATIONS_MANAGER => [
                ['title' => __('Unassigned batches'), 'count' => $unassignedBatches, 'description' => __('Batches without inspector assignment.'), 'route' => 'batches.index'],
                ['title' => __('Scheduling delays'), 'count' => $schedulingDelays, 'description' => __('Planned slaughter dates already passed.'), 'route' => 'slaughter-plans.index'],
                ['title' => __('Processing bottlenecks'), 'count' => $processingBottlenecks, 'description' => __('Pending batches blocking downstream flow.'), 'route' => 'batches.index'],
            ],
            BusinessUser::ROLE_INSPECTOR => [
                ['title' => __('Overdue inspections'), 'count' => $overdueInspections, 'description' => __('Batches older than one day still pending inspection.'), 'route' => 'post-mortem-inspections.index'],
                ['title' => __('Failed inspections'), 'count' => $failedInspections, 'description' => __('Rejected post-mortem outcomes needing action.'), 'route' => 'post-mortem-inspections.index'],
                ['title' => __('Capacity overload'), 'count' => $capacityOverload, 'description' => __('Inspections above available daily inspector capacity.'), 'route' => 'inspectors.index'],
            ],
            BusinessUser::ROLE_COMPLIANCE_OFFICER => [
                ['title' => __('Temperature breaches'), 'count' => $temperatureBreaches, 'description' => __('Warning or critical cold-chain records found.'), 'route' => 'warehouse-storages.index'],
                ['title' => __('Overdue compliance tasks'), 'count' => $openComplianceIssues, 'description' => __('Open compliance issues requiring closure.'), 'route' => 'compliance.index'],
                ['title' => __('Missing checklist submissions'), 'count' => $missingChecklistSubmissions, 'description' => __('Required inspections/checklists not yet submitted.'), 'route' => 'compliance.index'],
            ],
            BusinessUser::ROLE_TRANSPORT_MANAGER => [
                ['title' => __('Temperature deviations'), 'count' => $temperatureBreaches, 'description' => __('Cold-chain incidents during storage/dispatch window.'), 'route' => 'warehouse-storages.index'],
                ['title' => __('Delayed deliveries'), 'count' => $delayedDeliveries, 'description' => __('Trips delayed beyond expected delivery window.'), 'route' => 'transport-trips.index'],
                ['title' => __('Missing certificate attachments'), 'count' => $missingCertificateAttachments, 'description' => __('Trips lacking valid certificate linkage.'), 'route' => 'transport-trips.index'],
            ],
        ];

        $filteredQuickActions = collect($quickActions[$role] ?? [])
            ->filter(function (array $action) use ($request): bool {
                return $request->user()->canProcessorPermission((string) $action['permission']);
            })
            ->map(fn (array $action) => [
                'label' => (string) $action['label'],
                'url' => route((string) $action['route']),
            ])
            ->values()
            ->all();

        return [
            'metrics' => $metricsByRole[$role] ?? [],
            'alerts' => $alertsByRole[$role] ?? [],
            'quickActions' => $filteredQuickActions,
            'mapSummary' => [
                'facilities' => $facilitiesCount,
                'active_routes' => $activeTrips,
            ],
            'kpiPeriod' => $kpiPeriod,
            'kpiPeriodLabel' => $kpiPeriodLabel,
            'accountantSnapshot' => null,
        ];
    }

    /**
     * @return array{
     *   metrics: array<int, array<string, string|int>>,
     *   alerts: array<int, array<string, string|int>>,
     *   quickActions: array<int, array<string, string>>,
     *   mapSummary: array{facilities: int, active_routes: int},
     *   kpiPeriod: string,
     *   kpiPeriodLabel: string,
     *   accountantSnapshot: array{open_invoice_count: int, open_payable_count: int, allocation_line_count: int}
     * }
     */
    private function buildAccountantDashboardData(Request $request, int $businessId, string $kpiPeriod): array
    {
        $range = $this->accountantFinanceKpiDateRange($kpiPeriod);
        $kpiPeriodLabel = $range['label'];
        $now = now();

        $invoiceScoped = DB::table('finance_invoices')->where('business_id', $businessId);
        $payableScoped = DB::table('finance_payables')->where('business_id', $businessId);
        $allocationScoped = DB::table('finance_cost_allocations')->where('business_id', $businessId);

        if ($kpiPeriod !== 'all') {
            $this->applyFinanceDashboardDateWindow($invoiceScoped, 'issued_at', $range['start'], $range['end']);
            $this->applyFinanceDashboardDateWindow($payableScoped, 'issued_at', $range['start'], $range['end']);
            $allocationScoped->whereBetween('allocation_date', [$range['start']->toDateString(), $range['end']->toDateString()]);
        }

        $revenue = (float) (clone $invoiceScoped)->sum('total_amount');
        $arOutstanding = (float) (clone $invoiceScoped)->sum(DB::raw('GREATEST(total_amount - amount_paid, 0)'));
        $apOutstanding = (float) (clone $payableScoped)->sum(DB::raw('GREATEST(total_amount - amount_paid, 0)'));
        $allocatedCosts = (float) (clone $allocationScoped)->sum('amount');
        $grossMarginProxyPct = $revenue > 0
            ? round((($revenue - ($apOutstanding + $allocatedCosts)) / $revenue) * 100, 1)
            : 0.0;

        $openInvoiceCount = (int) (clone $invoiceScoped)->whereRaw('amount_paid < total_amount')->count();
        $openPayableCount = (int) (clone $payableScoped)->whereRaw('amount_paid < total_amount')->count();
        $allocationLineCount = (int) (clone $allocationScoped)->count();

        $overdueReceivablesCount = (int) DB::table('finance_invoices')
            ->where('business_id', $businessId)
            ->whereNotNull('due_date')
            ->where('due_date', '<', $now)
            ->whereRaw('amount_paid < total_amount')
            ->count();

        $overduePayablesCount = (int) DB::table('finance_payables')
            ->where('business_id', $businessId)
            ->whereNotNull('due_date')
            ->where('due_date', '<', $now)
            ->whereRaw('amount_paid < total_amount')
            ->count();

        $fmt = fn (float $n): string => number_format($n, 0, '.', ',');

        $metrics = [
            [
                'label' => __('Revenue (invoiced, :span)', ['span' => $kpiPeriodLabel]),
                'value' => $fmt($revenue).' '.__('RWF'),
                'description' => __('Total invoice amounts in the selected period.'),
            ],
            [
                'label' => __('AR outstanding'),
                'value' => $fmt($arOutstanding).' '.__('RWF'),
                'description' => __('Unpaid customer balance for invoices in the selected period.'),
            ],
            [
                'label' => __('AP outstanding'),
                'value' => $fmt($apOutstanding).' '.__('RWF'),
                'description' => __('Open supplier balances for payables in the selected period.'),
            ],
            [
                'label' => __('Allocated costs'),
                'value' => $fmt($allocatedCosts).' '.__('RWF'),
                'description' => __('Sum of cost allocation lines in the selected period.'),
            ],
            [
                'label' => __('Gross margin proxy'),
                'value' => (string) $grossMarginProxyPct.'%',
                'description' => __('(Revenue − AP outstanding − allocated costs) ÷ Revenue for the period.'),
            ],
            [
                'label' => __('Open AR documents'),
                'value' => (string) $openInvoiceCount,
                'description' => __('Invoices with a remaining balance in the selected period.'),
            ],
            [
                'label' => __('Open AP documents'),
                'value' => (string) $openPayableCount,
                'description' => __('Payables with a remaining balance in the selected period.'),
            ],
        ];

        $alerts = [];
        if ($overdueReceivablesCount > 0) {
            $alerts[] = [
                'title' => __('Overdue receivables'),
                'count' => $overdueReceivablesCount,
                'description' => __('Invoices past due with unpaid balance.'),
                'route' => 'finance.invoices.index',
            ];
        }
        if ($overduePayablesCount > 0) {
            $alerts[] = [
                'title' => __('Overdue payables'),
                'count' => $overduePayablesCount,
                'description' => __('Payables past due with unpaid balance.'),
                'route' => 'finance.payables.index',
            ];
        }

        $candidateActions = [
            ['label' => __('Review AR invoices'), 'route' => 'finance.invoices.index', 'permission' => BusinessUser::PERMISSION_MANAGE_AR_INVOICES],
            ['label' => __('Review AP payables'), 'route' => 'finance.payables.index', 'permission' => BusinessUser::PERMISSION_MANAGE_AP_PAYABLES],
            ['label' => __('Cost allocations'), 'route' => 'finance.cost-allocations.index', 'permission' => BusinessUser::PERMISSION_VIEW_FINANCE_REPORTS],
        ];

        $filteredQuickActions = collect($candidateActions)
            ->filter(fn (array $action) => $request->user()->canProcessorPermission((string) $action['permission'], $businessId))
            ->map(fn (array $action) => [
                'label' => (string) $action['label'],
                'url' => route((string) $action['route']),
            ])
            ->values()
            ->all();

        return [
            'metrics' => $metrics,
            'alerts' => $alerts,
            'quickActions' => $filteredQuickActions,
            'mapSummary' => [
                'facilities' => 0,
                'active_routes' => 0,
            ],
            'kpiPeriod' => $kpiPeriod,
            'kpiPeriodLabel' => $kpiPeriodLabel,
            'accountantSnapshot' => [
                'open_invoice_count' => $openInvoiceCount,
                'open_payable_count' => $openPayableCount,
                'allocation_line_count' => $allocationLineCount,
            ],
        ];
    }

    private function applyFinanceDashboardDateWindow(Builder $query, string $column, \Carbon\Carbon $start, \Carbon\Carbon $end): void
    {
        $query->where(function (Builder $q) use ($column, $start, $end): void {
            $q->whereBetween($column, [$start, $end])
                ->orWhere(function (Builder $fallback) use ($column, $start, $end): void {
                    $fallback->whereNull($column)->whereBetween('created_at', [$start, $end]);
                });
        });
    }

    /**
     * @return array{start: \Carbon\Carbon, end: \Carbon\Carbon, label: string}
     */
    private function accountantFinanceKpiDateRange(string $period): array
    {
        $now = now();

        return match ($period) {
            'day' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => $now->format('M j, Y'),
            ],
            'month' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
                'label' => $now->format('F Y'),
            ],
            'year' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
                'label' => (string) $now->year,
            ],
            default => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => (string) __('All time'),
            ],
        };
    }

    /**
     * @return array{start: \Carbon\Carbon, end: \Carbon\Carbon, label: string, shortLabel: string}
     */
    private function kpiDateRange(string $period): array
    {
        $now = now();

        return match ($period) {
            'day' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => $now->format('M j, Y'),
                'shortLabel' => (string) __('Today'),
            ],
            'year' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
                'label' => (string) $now->year,
                'shortLabel' => (string) __('This year'),
            ],
            default => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
                'label' => $now->format('F Y'),
                'shortLabel' => (string) __('This month'),
            ],
        };
    }
}
