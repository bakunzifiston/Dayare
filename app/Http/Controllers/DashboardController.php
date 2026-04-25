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
use Illuminate\Http\Request;
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
        ]);
    }

    /**
     * @return array{
     *   metrics: array<int, array<string, string|int>>,
     *   alerts: array<int, array<string, string|int>>,
     *   quickActions: array<int, array<string, string>>,
     *   mapSummary: array{facilities: int, active_routes: int}
     * }
     */
    private function buildRoleDashboardData(Request $request, string $role, int $businessId): array
    {
        $today = now()->startOfDay();
        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();
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

        $animalsProcessedToday = (int) SlaughterExecution::query()
            ->whereIn('id', $executionIds)
            ->whereDate('slaughter_time', $today)
            ->sum('actual_animals_slaughtered');
        $animalsProcessedWeek = (int) SlaughterExecution::query()
            ->whereIn('id', $executionIds)
            ->whereBetween('slaughter_time', [$weekStart, $weekEnd])
            ->sum('actual_animals_slaughtered');

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
        $complianceScore = $certificatesIssued > 0
            ? (int) round(($validCertificates / max(1, $certificatesIssued)) * 100)
            : 0;

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
            BusinessUser::ROLE_LOGISTICS_MANAGER => [
                ['label' => __('Create transport trip'), 'route' => 'transport-trips.create', 'permission' => BusinessUser::PERMISSION_CREATE_TRANSPORT_TRIP],
                ['label' => __('Confirm deliveries'), 'route' => 'delivery-confirmations.index', 'permission' => BusinessUser::PERMISSION_CONFIRM_DELIVERY],
                ['label' => __('Review trip statuses'), 'route' => 'transport-trips.index', 'permission' => BusinessUser::PERMISSION_TRACK_DELIVERY_STATUS],
            ],
        ];

        $metricsByRole = [
            BusinessUser::ROLE_ORG_ADMIN => [
                ['label' => __('Animals processed today'), 'value' => $animalsProcessedToday, 'description' => __('Used for same-day throughput decisions.')],
                ['label' => __('Animals processed this week'), 'value' => $animalsProcessedWeek, 'description' => __('Used for weekly output planning.')],
                ['label' => __('Total batches created'), 'value' => $totalBatches, 'description' => __('Tracks production volume and traceability load.')],
                ['label' => __('Certificates issued (valid / expired)'), 'value' => $validCertificates.' / '.$expiredCertificates, 'description' => __('Signals certification health and legal exposure.')],
                ['label' => __('Overall compliance score'), 'value' => $complianceScore.'%', 'description' => __('Composite signal for audit readiness.')],
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
            BusinessUser::ROLE_LOGISTICS_MANAGER => [
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
            BusinessUser::ROLE_LOGISTICS_MANAGER => [
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
        ];
    }
}
